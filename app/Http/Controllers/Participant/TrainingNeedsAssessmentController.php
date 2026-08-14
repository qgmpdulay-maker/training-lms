<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
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

        return view('participant.training-needs-assessment.index', compact('trainings', 'existingRecommendation'));
    }

    public function storeRecommendation(Request $request)
    {
        $catalog = collect(config('trainings.catalog'));

        $validated = $request->validate([
            'training_slug' => ['required', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question' => ['required', 'string'],
            'answers.*.selected' => ['required', 'string'],
            'answers.*.category' => ['nullable', 'string'],
            'answers.*.points' => ['nullable', 'integer'],
            'answers.*.hours' => ['nullable', 'integer'],
            'category_scores' => ['required', 'array'],
            'category_scores.*' => ['integer'],
            'top_category' => ['nullable', 'string'],
            'max_hours' => ['nullable', 'integer'],
        ]);

        $training = $catalog->firstWhere('slug', $validated['training_slug']);
        $user = Auth::user();

        $user->trainingNeedsAssessments()->create([
            'answers' => $validated['answers'],
            'category_scores' => $validated['category_scores'],
            'top_category' => $validated['top_category'] ?? null,
            'max_hours' => $validated['max_hours'] ?? null,
            'recommended_training_slug' => $training['slug'],
            'recommended_training_title' => $training['title'],
            'recommended_training_category' => $training['category'] ?? null,
        ]);

        $user->forceFill([
            'recommended_training_slug' => $validated['training_slug'],
            'recommended_training_at' => now(),
        ])->save();

        return response()->noContent();
    }
}
