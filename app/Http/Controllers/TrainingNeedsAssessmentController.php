<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingNeedsAssessmentController extends Controller
{
    public function index()
    {
        $trainings = config('trainings.catalog');

        $existingRecommendation = Auth::user()->recommended_training_slug
            ? collect($trainings)->firstWhere('slug', Auth::user()->recommended_training_slug)
            : null;

        return view('trainings.needs-assessment', compact('trainings', 'existingRecommendation'));
    }

    public function storeRecommendation(Request $request)
    {
        $catalog = collect(config('trainings.catalog'));

        $validated = $request->validate([
            'training_slug' => ['required', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
        ]);

        Auth::user()->forceFill([
            'recommended_training_slug' => $validated['training_slug'],
            'recommended_training_at' => now(),
        ])->save();

        return response()->noContent();
    }
}
