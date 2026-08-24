<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    const MAX_MODULE_ROWS = 8;

    const RATING_SCALE = [
        1 => '1 (Poor)',
        2 => '2 (Unsatisfactory)',
        3 => '3 (Satisfactory)',
        4 => '4 (Very Satisfactory)',
        5 => '5 (Outstanding)',
    ];

    public function edit(Request $request, TrainingRequest $trainingRequest): View
    {
        $this->authorizeRegion($request, $trainingRequest);

        $trainingRequest->load('user', 'trainingEvaluation');

        $moduleRows = $trainingRequest->trainingEvaluation?->module_ratings ?? [];
        $moduleRows = array_pad($moduleRows, self::MAX_MODULE_ROWS, ['module' => '', 'module_rating' => '', 'trainer_rating' => '', 'comment' => '']);

        return view('admin.evaluations.edit', [
            'trainingRequest' => $trainingRequest,
            'moduleRows' => $moduleRows,
            'ratingScale' => self::RATING_SCALE,
        ]);
    }

    public function update(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorizeRegion($request, $trainingRequest);

        $validated = $request->validate([
            'pretest_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'posttest_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'module' => ['nullable', 'array'],
            'module.*' => ['nullable', 'string', 'max:255'],
            'module_rating' => ['nullable', 'array'],
            'module_rating.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'trainer_rating' => ['nullable', 'array'],
            'trainer_rating.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'array'],
            'comment.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $rowCount = max(
            count($validated['module'] ?? []),
            count($validated['module_rating'] ?? []),
            count($validated['trainer_rating'] ?? []),
            count($validated['comment'] ?? []),
        );

        $moduleRatings = collect(range(0, $rowCount - 1))
            ->map(fn ($i) => [
                'module' => $validated['module'][$i] ?? null,
                'module_rating' => $validated['module_rating'][$i] ?? null,
                'trainer_rating' => $validated['trainer_rating'][$i] ?? null,
                'comment' => $validated['comment'][$i] ?? null,
            ])
            // A row only counts as "used" if something was actually entered — but once
            // it is, keep it even if the module code itself was left blank, so a
            // forgotten module code doesn't silently throw away the ratings/comment.
            ->filter(fn ($row) => filled($row['module']) || filled($row['module_rating']) || filled($row['trainer_rating']) || filled($row['comment']))
            ->values()
            ->map(fn ($row, $i) => [...$row, 'module' => $row['module'] ?: 'Module '.($i + 1)])
            ->all();

        $trainingRequest->trainingEvaluation()->updateOrCreate([], [
            'module_ratings' => $moduleRatings,
            'pretest_score' => $validated['pretest_score'] ?? null,
            'posttest_score' => $validated['posttest_score'] ?? null,
        ]);

        $this->reflectTrainerRating($trainingRequest->training_title);

        return Redirect::route('admin.tools')->with('status', "Evaluation saved for {$trainingRequest->training_title}.");
    }

    /**
     * Regional admins may only touch evaluations for requests tagged to their
     * own region; Super Admin can touch any of them.
     */
    private function authorizeRegion(Request $request, TrainingRequest $trainingRequest): void
    {
        $user = $request->user();

        abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);
    }

    /**
     * Best-effort: if exactly one instructor teaches this training, roll the
     * average trainer rating across all evaluations for it into their profile.
     */
    private function reflectTrainerRating(string $trainingTitle): void
    {
        $instructors = Instructor::where('training_type', $trainingTitle)->get();

        if ($instructors->count() !== 1) {
            return;
        }

        $ratings = TrainingRequest::where('training_title', $trainingTitle)
            ->with('trainingEvaluation')
            ->get()
            ->pluck('trainingEvaluation.module_ratings')
            ->filter()
            ->flatten(1)
            ->pluck('trainer_rating')
            ->filter(fn ($r) => is_numeric($r));

        if ($ratings->isEmpty()) {
            return;
        }

        $instructors->first()->update(['rating' => round($ratings->avg(), 2)]);
    }
}
