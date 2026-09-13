<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\TrainingRequestConfirmation;
use App\Models\TrainingRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function create(Request $request): View
    {
        // Re-hydrate names for previously selected participants after a
        // validation failure — old() only kept their ids.
        $selectedParticipants = User::whereIn('id', old('participant_ids', []))
            ->get(['id', 'name', 'organization', 'region']);

        return view('admin.super-admin.trainings.create', [
            'trainings' => config('trainings.catalog'),
            'selectedSlug' => $request->query('training'),
            'agencyTypeLabels' => TrainingRequest::$agencyTypeLabels,
            'regions' => config('regions.list'),
            'selectedParticipants' => $selectedParticipants,
            'defaultPreferredDate' => self::nextOfficeDay(now()->addMonthNoOverflow()->startOfDay())->toDateString(),
        ]);
    }

    /**
     * Backs the participant picker on the create form. The participant roster
     * runs into the thousands nationwide, so it's searched on demand (scoped
     * to the chosen region) rather than shipped to the browser in one go —
     * paginated 10 at a time rather than silently truncated to the first 10
     * matches. Regional admins are eligible to attend too — only Super
     * Admins are excluded, since they're purely administrative.
     */
    public function participants(Request $request): JsonResponse
    {
        $region = $request->query('region');
        $search = trim((string) $request->query('q', ''));
        $page = max((int) $request->query('page', 1), 1);

        $participants = User::whereIn('role', [User::ROLE_PARTICIPANT, User::ROLE_ADMIN])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10, ['id', 'name', 'organization', 'region'], 'page', $page);

        return response()->json([
            'data' => $participants->items(),
            'current_page' => $participants->currentPage(),
            'last_page' => $participants->lastPage(),
            'total' => $participants->total(),
        ]);
    }

    /**
     * Rolls a date forward onto the nearest weekday — trainings are only
     * held on office days, so Sat/Sun are never valid.
     */
    private static function nextOfficeDay(Carbon $date): Carbon
    {
        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }

    public function store(Request $request): RedirectResponse
    {
        $catalog = collect(config('trainings.catalog'));
        $user = $request->user();

        $validated = $request->validate([
            'training_type' => ['required', 'in:catalog,custom'],
            'training_slug' => ['required_if:training_type,catalog', 'nullable', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
            'custom_training_title' => ['required_if:training_type,custom', 'nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'in:'.implode(',', config('regions.list'))],
            'agency_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$agencyTypeLabels))],
            'requesting_agency' => ['required', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email', 'max:255'],
            'number_of_participants' => ['required', 'integer', 'min:1', 'max:1000'],
            'preferred_date' => [
                'required', 'date',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isWeekend()) {
                        $fail('Trainings can only be scheduled on weekdays (Monday through Friday).');
                    }
                },
            ],
            'venue' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:2000'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', [User::ROLE_PARTICIPANT, User::ROLE_ADMIN])),
            ],
        ]);

        if ($validated['training_type'] === 'custom') {
            // Typed in rather than picked from the TA catalog — treated as an
            // APB training, and its slug is derived from the title since there's
            // no catalog entry to key off of.
            $trainingSlug = Str::slug($validated['custom_training_title']);
            $trainingTitle = $validated['custom_training_title'];
            $category = TrainingRequest::CATEGORY_APB;
        } else {
            $training = $catalog->firstWhere('slug', $validated['training_slug']);
            $trainingSlug = $validated['training_slug'];
            $trainingTitle = $training['title'];
            $category = TrainingRequest::CATEGORY_TA;
        }

        $trainingRequest = new TrainingRequest($validated);
        $trainingRequest->user_id = $user->id;
        $trainingRequest->training_slug = $trainingSlug;
        $trainingRequest->training_title = $trainingTitle;
        $trainingRequest->category = $category;
        $trainingRequest->source = TrainingRequest::SOURCE_ADMIN_SCHEDULED;
        $trainingRequest->signature_name = $user->name;
        // Super Admin is scheduling this directly — it doesn't go through the
        // regional-office review pipeline (submitted -> under_review -> approved).
        $trainingRequest->status = TrainingRequest::STATUS_APPROVED;
        $trainingRequest->save();
        $trainingRequest->reference_number = sprintf('TR-%s-%05d', now()->year, $trainingRequest->id);
        $trainingRequest->save();

        $trainingRequest->participants()->sync($validated['participant_ids'] ?? []);

        try {
            Mail::to($trainingRequest->contact_email)->send(new TrainingRequestConfirmation($trainingRequest));
        } catch (\Throwable $e) {
            Log::error('Failed to send training schedule confirmation email: '.$e->getMessage());
        }

        return Redirect::route('admin.calendar')
            ->with('status', "\"{$trainingRequest->training_title}\" was scheduled for {$trainingRequest->preferred_date->format('M j, Y')} in {$trainingRequest->region}.");
    }
}
