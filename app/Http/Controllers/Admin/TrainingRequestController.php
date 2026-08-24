<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TrainingRequestConfirmation;
use App\Mail\TrainingRequestSubmitted;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingRequestController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();

        $participants = User::where('role', User::ROLE_PARTICIPANT)
            ->where('region', $user->region)
            ->orderBy('name')
            ->get(['id', 'name', 'organization', 'participant_type']);

        return view('admin.training-requests.create', [
            'trainings' => config('trainings.catalog'),
            'selectedSlug' => $request->query('training'),
            'user' => $user,
            'participants' => $participants,
            'categoryLabels' => TrainingRequest::$categoryLabels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $catalog = collect(config('trainings.catalog'));
        $user = $request->user();

        $validated = $request->validate([
            'training_slug' => ['required', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(TrainingRequest::$categoryLabels))],
            'requesting_agency' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email', 'max:255'],
            'number_of_participants' => ['required', 'integer', 'min:1', 'max:1000'],
            'preferred_date' => ['required', 'date', 'after:today'],
            'venue' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:2000'],
            'tna_completed' => ['accepted'],
            'tna_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'logistics_acknowledged' => ['accepted'],
            'signature_name' => ['required', 'string', 'max:255'],
            'signed_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', User::ROLE_PARTICIPANT)->where('region', $user->region)),
            ],
        ]);

        $training = $catalog->firstWhere('slug', $validated['training_slug']);

        $trainingRequest = new TrainingRequest($validated);
        $trainingRequest->user_id = $user->id;
        $trainingRequest->training_title = $training['title'];
        // Regional admins can only file requests under their own region.
        $trainingRequest->region = $user->region;
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

        // Every selected participant is now attached to this request — certificate
        // details, category, and region tagged on it apply to the whole group.
        $trainingRequest->participants()->sync($validated['participant_ids'] ?? []);

        try {
            Mail::to(config('trainings.notify_email'))->send(new TrainingRequestSubmitted($trainingRequest));
            Mail::to($trainingRequest->contact_email)->send(new TrainingRequestConfirmation($trainingRequest));
        } catch (\Throwable $e) {
            Log::error('Failed to send training request emails: '.$e->getMessage());
        }

        return Redirect::route('admin.summary')
            ->with('status', "Training request {$trainingRequest->reference_number} submitted for {$trainingRequest->training_title}.");
    }
}
