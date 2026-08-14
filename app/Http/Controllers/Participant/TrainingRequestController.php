<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\Auth;

class TrainingRequestController extends Controller
{
    /**
     * Participants no longer submit their own training requests — OCD schedules
     * them. This lists the trainings a participant has been confirmed (approved)
     * for that haven't happened yet.
     */
    public function index()
    {
        $upcomingTrainings = Auth::user()->trainingRequests()
            ->where('status', TrainingRequest::STATUS_APPROVED)
            ->where('preferred_date', '>=', now()->toDateString())
            ->orderBy('preferred_date')
            ->get();

        return view('participant.training-requests.index', compact('upcomingTrainings'));
    }

    public function show(TrainingRequest $trainingRequest)
    {
        abort_unless($trainingRequest->user_id === Auth::id(), 403);

        return view('participant.training-requests.show', compact('trainingRequest'));
    }
}
