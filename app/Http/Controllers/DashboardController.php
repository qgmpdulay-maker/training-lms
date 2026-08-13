<?php

namespace App\Http\Controllers;

use App\Models\TrainingRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $takenTrainings = $user->trainingRequests()
            ->where('status', TrainingRequest::STATUS_COMPLETED)
            ->latest()
            ->get();

        $recommendedTraining = $user->recommended_training_slug
            ? collect(config('trainings.catalog'))->firstWhere('slug', $user->recommended_training_slug)
            : null;

        return view('dashboard', compact('takenTrainings', 'recommendedTraining'));
    }
}
