<?php

namespace App\Http\Controllers;

use App\Mail\TrainingRequestConfirmation;
use App\Mail\TrainingRequestSubmitted;
use App\Models\TrainingRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * Public, unauthenticated Technical Assistance request form for LGUs/NGAs —
 * the successor to the old admin-only "Request Training" tool (removed when
 * Schedule Training was introduced). Every request lands in STATUS_SUBMITTED
 * and enters the same review pipeline OCD Regional Offices already manage
 * from the admin Summary page.
 */
class PublicTrainingRequestController extends Controller
{
    public function create(Request $request): View
    {
        return view('public.training-requests.create', [
            'trainings' => config('trainings.catalog'),
            'selectedSlug' => $request->query('training'),
            'agencyTypeLabels' => TrainingRequest::$agencyTypeLabels,
            'regions' => config('regions.list'),
            'defaultPreferredDate' => self::nextOfficeDay(now()->addMonthNoOverflow()->startOfDay())->toDateString(),
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

        $validated = $request->validate([
            'training_slug' => ['required', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
            'agency_type' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$agencyTypeLabels))],
            'requesting_agency' => ['required', 'string', 'max:255'],
            'lgu' => ['nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'in:'.implode(',', config('regions.list'))],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email', 'max:255'],
            'number_of_participants' => ['required', 'integer', 'min:1', 'max:1000'],
            'preferred_date' => [
                'required', 'date', 'after:today',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isWeekend()) {
                        $fail('Trainings can only be scheduled on weekdays (Monday through Friday).');
                    }
                },
            ],
            'venue' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:2000'],
            'tna_completed' => ['accepted'],
            'tna_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'logistics_acknowledged' => ['accepted'],
            'signature_name' => ['required', 'string', 'max:255'],
            'signed_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            // Honeypot — invisible to real visitors, so anything that fills it in is a bot.
            'website' => ['prohibited'],
        ]);

        $training = $catalog->firstWhere('slug', $validated['training_slug']);

        $trainingRequest = new TrainingRequest(collect($validated)->except(['tna_file', 'signed_letter', 'website'])->all());
        $trainingRequest->training_title = $training['title'];
        // Every training requested through this portal is Technical Assistance —
        // OCD doesn't run APB trainings, so there's nothing to choose here.
        $trainingRequest->category = TrainingRequest::CATEGORY_TA;
        $trainingRequest->tna_completed = $request->boolean('tna_completed');
        $trainingRequest->logistics_acknowledged = $request->boolean('logistics_acknowledged');

        if ($request->hasFile('tna_file')) {
            $trainingRequest->tna_file_path = $request->file('tna_file')->store('training-requests/tna', 'public');
        }

        if ($request->hasFile('signed_letter')) {
            $trainingRequest->signed_letter_path = $request->file('signed_letter')->store('training-requests/letters', 'public');
        }

        $trainingRequest->save();
        $trainingRequest->reference_number = sprintf('TR-%s-%05d', now()->year, $trainingRequest->id);
        $trainingRequest->save();

        try {
            Mail::to(config('trainings.notify_email'))->send(new TrainingRequestSubmitted($trainingRequest));
            Mail::to($trainingRequest->contact_email)->send(new TrainingRequestConfirmation($trainingRequest));
        } catch (\Throwable $e) {
            Log::error('Failed to send public training request emails: '.$e->getMessage());
        }

        return Redirect::route('public.training-requests.submitted')
            ->with('reference_number', $trainingRequest->reference_number)
            ->with('training_title', $trainingRequest->training_title);
    }

    public function submitted(): View
    {
        return view('public.training-requests.submitted', [
            'referenceNumber' => session('reference_number'),
            'trainingTitle' => session('training_title'),
        ]);
    }
}
