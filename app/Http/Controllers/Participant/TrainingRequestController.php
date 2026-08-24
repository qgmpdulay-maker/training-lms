<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Support\Facades\Auth;

class TrainingRequestController extends Controller
{
    /**
     * Participants no longer submit their own training requests — OCD schedules
     * them, either as their own submission or by selecting the participant into
     * a bulk request. This lists the trainings a participant has been confirmed
     * (approved) for that haven't happened yet.
     */
    public function index()
    {
        $upcomingTrainings = TrainingRequest::involvingUser(Auth::user())
            ->where('status', TrainingRequest::STATUS_APPROVED)
            ->where('preferred_date', '>=', now()->toDateString())
            ->orderBy('preferred_date')
            ->get();

        return view('participant.training-requests.index', compact('upcomingTrainings'));
    }

    public function show(TrainingRequest $trainingRequest)
    {
        $user = Auth::user();
        $isOwner = $trainingRequest->user_id === $user->id;
        $isParticipant = $trainingRequest->participants->contains($user->id);

        abort_unless($isOwner || $isParticipant, 403);

        return view('participant.training-requests.show', compact('trainingRequest'));
    }
}
