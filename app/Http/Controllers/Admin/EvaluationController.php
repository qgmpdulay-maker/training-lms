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
            'participantScoreRows' => $this->participantScoreRows($trainingRequest),
        ]);
    }

    public function update(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $this->authorizeRegion($request, $trainingRequest);

        $validated = $request->validate([
            'pretest_score' => ['nullable', 'array'],
            'pretest_score.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'posttest_score' => ['nullable', 'array'],
            'posttest_score.*' => ['nullable', 'integer', 'min:0', 'max:100'],
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

        $participantScores = $trainingRequest->effectiveParticipants()
            ->map(fn ($participant) => [
                'user_id' => $participant->id,
                'pretest_score' => $validated['pretest_score'][$participant->id] ?? null,
                'posttest_score' => $validated['posttest_score'][$participant->id] ?? null,
            ])
            ->filter(fn ($row) => $row['pretest_score'] !== null || $row['posttest_score'] !== null)
            ->values()
            ->all();

        $trainingRequest->trainingEvaluation()->updateOrCreate([], [
            'module_ratings' => $moduleRatings,
            'participant_scores' => $participantScores,
        ]);

        Instructor::reflectRatingForTraining($trainingRequest->training_title);

        return Redirect::route('admin.tools')->with('status', "Evaluation saved for {$trainingRequest->training_title}.");
    }

    /**
     * One row per participant this session actually covers, for the pretest/
     * posttest score table. Prefilled from participant_scores when the
     * evaluation has already been saved in the new per-taker shape; falls
     * back to the old single session-wide score (same value on every row)
     * when only the legacy scalar pretest_score/posttest_score exist, so an
     * evaluation entered before this feature existed can just be re-saved to
     * pick up the new shape.
     *
     * @return \Illuminate\Support\Collection<int, array{user_id: int, name: string, pretest_score: ?int, posttest_score: ?int}>
     */
    private function participantScoreRows(TrainingRequest $trainingRequest): \Illuminate\Support\Collection
    {
        $evaluation = $trainingRequest->trainingEvaluation;
        $scoresByParticipant = collect($evaluation?->participant_scores ?? [])->keyBy('user_id');

        return $trainingRequest->effectiveParticipants()->map(function ($participant) use ($evaluation, $scoresByParticipant) {
            $existing = $scoresByParticipant->get($participant->id);

            return [
                'user_id' => $participant->id,
                'name' => $participant->name,
                'pretest_score' => $existing['pretest_score'] ?? $evaluation?->pretest_score,
                'posttest_score' => $existing['posttest_score'] ?? $evaluation?->posttest_score,
            ];
        });
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
}
