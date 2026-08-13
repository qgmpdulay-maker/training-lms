<?php

namespace App\Http\Controllers;

use App\Mail\TrainingRequestConfirmation;
use App\Mail\TrainingRequestSubmitted;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TrainingRequestController extends Controller
{
    public function index()
    {
        $requests = Auth::user()->trainingRequests()->latest()->get();

        return view('trainings.index', compact('requests'));
    }

    public function create(Request $request)
    {
        $trainings = config('trainings.catalog');
        $selectedSlug = $request->query('training');
        $user = Auth::user();

        return view('trainings.create', compact('trainings', 'selectedSlug', 'user'));
    }

    public function store(Request $request)
    {
        $catalog = collect(config('trainings.catalog'));

        $validated = $request->validate([
            'training_slug' => ['required', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
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
        ]);

        $training = $catalog->firstWhere('slug', $validated['training_slug']);

        $trainingRequest = new TrainingRequest($validated);
        $trainingRequest->user_id = Auth::id();
        $trainingRequest->training_title = $training['title'];
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
            Log::error('Failed to send training request emails: '.$e->getMessage());
        }

        return redirect()->route('training-requests.show', $trainingRequest)
            ->with('status', 'Your training request has been submitted.');
    }

    public function show(TrainingRequest $trainingRequest)
    {
        abort_unless($trainingRequest->user_id === Auth::id(), 403);

        return view('trainings.show', compact('trainingRequest'));
    }
}
