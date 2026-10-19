<?php

namespace App\Services;

use App\Models\TrainingRequest;
use Illuminate\Support\Collection;

/**
 * Turns one training's evaluations — the admin's own (TrainingEvaluation) and
 * the participants' (ParticipantEvaluation) — into the L1/L2 figures the
 * reports show: per-module and per-trainer averages, 1-5 distributions,
 * anonymous comments, and pre/post-test statistics.
 *
 * Shared by the two places that read it, both read-only: the Tools page's
 * expandable session rows (ToolsController) and the Summary page's Evaluations
 * card, which opens a training's summary on its own page (SummaryController).
 */
class EvaluationSummary
{
    /**
     * The fields shown on a session's collapsed row. Expects trainingEvaluation
     * and participantEvaluations loaded, plus participants_count.
     *
     * @return array<string, mixed>
     */
    public function header(TrainingRequest $trainingRequest): array
    {
        $evaluation = $trainingRequest->trainingEvaluation;

        $trainerScores = collect($evaluation->module_ratings ?? [])->pluck('trainer_rating')
            ->merge($trainingRequest->participantEvaluations->pluck('module_ratings')->filter()->flatten(1)->pluck('trainer_rating'))
            ->filter(fn ($r) => is_numeric($r));

        return [
            'training_request_id' => $trainingRequest->id,
            'training_title' => $trainingRequest->training_title,
            'preferred_date' => $trainingRequest->preferred_date,
            'venue' => $trainingRequest->venue,
            'updated_at' => collect([$evaluation?->updated_at, $trainingRequest->participantEvaluations->max('updated_at')])->filter()->max(),
            'overall_trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
            'participant_response_count' => $trainingRequest->participantEvaluations->count(),
            // Same rule as TrainingRequest::effectiveParticipants(): the
            // selected roster, else the submitter alone.
            'participant_total' => $trainingRequest->participants_count ?: ($trainingRequest->user_id !== null ? 1 : 0),
        ];
    }

    /**
     * One session's full L1/L2 breakdown for its expanded row. Expects
     * trainingEvaluation, participantEvaluations.user, and instructors loaded,
     * plus participants_count.
     *
     * @return array<string, mixed>
     */
    public function forSession(TrainingRequest $trainingRequest): array
    {
        $evaluation = $trainingRequest->trainingEvaluation;
        $moduleRatings = collect($evaluation->module_ratings ?? []);
        $participantModuleRatings = $trainingRequest->participantEvaluations->pluck('module_ratings')->filter()->flatten(1);
        $participantInstructorRatings = $trainingRequest->participantEvaluations->pluck('instructor_ratings')->filter()->flatten(1);
        $trainerLabel = $this->trainerLabelFor($trainingRequest->instructors);

        $moduleNames = $moduleRatings->pluck('module')
            ->merge($participantModuleRatings->pluck('module'))
            ->filter()
            ->unique()
            ->values();

        $modules = $moduleNames
            ->map(function ($moduleName) use ($moduleRatings, $participantModuleRatings) {
                $adminRows = $moduleRatings->where('module', $moduleName);
                $participantRows = $participantModuleRatings->where('module', $moduleName);

                $moduleScores = $adminRows->pluck('module_rating')->filter(fn ($r) => is_numeric($r));
                $trainerScores = $adminRows->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));
                $participantScores = $participantRows->pluck('module_rating')->filter(fn ($r) => is_numeric($r));
                $participantTrainerScores = $participantRows->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));

                return [
                    'module' => $moduleName,
                    'module_rating' => $moduleScores->isNotEmpty() ? round($moduleScores->avg(), 2) : null,
                    'trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
                    'participant_rating' => $participantScores->isNotEmpty() ? round($participantScores->avg(), 2) : null,
                    'participant_trainer_rating' => $participantTrainerScores->isNotEmpty() ? round($participantTrainerScores->avg(), 2) : null,
                    'participant_responses' => $participantScores->count(),
                    'rating_distribution' => $this->ratingDistribution($participantScores),
                    'comments' => $participantRows->pluck('comment')->filter(fn ($c) => filled(trim((string) $c)))->values()->all(),
                ];
            });

        // Per-module Trainer's Rating summary — pools trainer_rating from
        // both the admin's own module_ratings and every participant's
        // per-module trainer rating, matching the TOR's "Summary of
        // Trainers Rating per Module" table (grouped by module, not by
        // instructor — see the separate whole-training instructorRatings
        // below for the per-instructor view).
        $trainerRatingsByModule = $moduleNames
            ->map(function ($moduleName) use ($moduleRatings, $participantModuleRatings, $trainerLabel) {
                $pooledScores = $moduleRatings->where('module', $moduleName)->pluck('trainer_rating')
                    ->merge($participantModuleRatings->where('module', $moduleName)->pluck('trainer_rating'))
                    ->filter(fn ($r) => is_numeric($r));

                return [
                    'module' => $moduleName,
                    'trainer' => $trainerLabel['name'],
                    'organization' => $trainerLabel['organization'],
                    'rating' => $pooledScores->isNotEmpty() ? round($pooledScores->avg(), 2) : null,
                    'responses' => $pooledScores->count(),
                    'rating_distribution' => $this->ratingDistribution($pooledScores),
                ];
            })
            ->filter(fn ($row) => $row['rating'] !== null)
            ->values();

        $instructorRatings = $participantInstructorRatings
            ->groupBy('instructor_id')
            ->map(function ($rows, $instructorId) use ($trainingRequest) {
                $scores = $rows->pluck('rating')->filter(fn ($r) => is_numeric($r));
                $instructor = $trainingRequest->instructors->firstWhere('id', (int) $instructorId);

                return [
                    'instructor' => $instructor?->name ?? 'Unknown instructor',
                    'agency_organization' => $instructor?->agency_organization,
                    'rating' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
                    'responses' => $scores->count(),
                    'rating_distribution' => $this->ratingDistribution($scores),
                    'comments' => $rows->pluck('comment')->filter(fn ($c) => filled(trim((string) $c)))->values()->all(),
                ];
            })
            ->filter(fn ($row) => $row['rating'] !== null)
            ->values();

        // Per-taker pretest/posttest pairs live in participant_scores once an
        // evaluation has been saved through the per-participant form; older
        // evaluations only ever recorded one session-wide pair, so those are
        // treated as a single-person sample rather than silently dropped.
        $participantScores = collect($evaluation?->participant_scores ?? []);
        $pretestScores = $participantScores->isNotEmpty()
            ? $participantScores->pluck('pretest_score')->filter(fn ($s) => is_numeric($s))
            : collect([$evaluation?->pretest_score])->filter(fn ($s) => is_numeric($s));
        $posttestScores = $participantScores->isNotEmpty()
            ? $participantScores->pluck('posttest_score')->filter(fn ($s) => is_numeric($s))
            : collect([$evaluation?->posttest_score])->filter(fn ($s) => is_numeric($s));

        $moduleMatrixModules = $trainingRequest->participantEvaluations
            ->pluck('module_ratings')->filter()->flatten(1)
            ->pluck('module')->filter()->unique()->values();

        $moduleMatrix = $trainingRequest->participantEvaluations->map(function ($participantEvaluation) use ($moduleMatrixModules) {
            $ratingsByModule = collect($participantEvaluation->module_ratings ?? [])->keyBy('module');

            $scores = $moduleMatrixModules->mapWithKeys(fn ($module) => [
                $module => [
                    'module_rating' => $ratingsByModule[$module]['module_rating'] ?? null,
                    'trainer_rating' => $ratingsByModule[$module]['trainer_rating'] ?? null,
                ],
            ]);

            $allCells = $scores->flatMap(fn ($cell) => [$cell['module_rating'], $cell['trainer_rating']])
                ->filter(fn ($r) => is_numeric($r));

            return [
                'participant' => $participantEvaluation->user?->name ?? 'Unknown participant',
                'scores' => $scores->all(),
                'overall' => $allCells->isNotEmpty() ? round($allCells->avg(), 2) : null,
            ];
        })->values();

        return [
            ...$this->header($trainingRequest),
            'modules' => $modules,
            'pretest_stats' => $this->scoreStatistics($pretestScores),
            'posttest_stats' => $this->scoreStatistics($posttestScores),
            'instructor_ratings' => $instructorRatings,
            'trainer_ratings_by_module' => $trainerRatingsByModule,
            'module_matrix_columns' => $moduleMatrixModules->all(),
            'module_matrix' => $moduleMatrix,
        ];
    }

    /**
     * Display label for the "Trainer Name / Organization" columns on the
     * per-module trainer summary — nothing in the schema links a specific
     * module to a specific co-instructor, so this names whoever is on file
     * for the training as a whole (matching the convention already used by
     * EvaluationController::reflectTrainerRating for the single-instructor
     * case).
     *
     * @return array{name: ?string, organization: ?string}
     */
    private function trainerLabelFor(Collection $instructors): array
    {
        if ($instructors->count() === 1) {
            $instructor = $instructors->first();

            return ['name' => $instructor->name, 'organization' => $instructor->agency_organization ?? $instructor->lgu];
        }

        if ($instructors->count() > 1) {
            return ['name' => $instructors->pluck('name')->implode(', '), 'organization' => null];
        }

        return ['name' => null, 'organization' => null];
    }

    /**
     * Count of 1-5 ratings among the given scores, for the distribution
     * tables on the L1 evaluation section — comments are shown anonymously
     * alongside these, matching how evaluation feedback is typically handled.
     *
     * @return array<int, int>
     */
    private function ratingDistribution(Collection $scores): array
    {
        return collect(range(1, 5))
            ->mapWithKeys(fn ($value) => [$value => $scores->filter(fn ($r) => (int) $r === $value)->count()])
            ->all();
    }

    /**
     * Mean/median/mode/min/max/count for a set of numeric scores — used for
     * the L2 pretest/posttest stats table. Mode ties break toward the
     * smallest value for determinism.
     *
     * @return array{count: int, mean: ?float, median: ?float, mode: ?float, min: ?float, max: ?float}
     */
    private function scoreStatistics(Collection $scores): array
    {
        $scores = $scores->filter(fn ($s) => is_numeric($s))->map(fn ($s) => (float) $s)->values();
        $count = $scores->count();

        if ($count === 0) {
            return ['count' => 0, 'mean' => null, 'median' => null, 'mode' => null, 'min' => null, 'max' => null];
        }

        $sorted = $scores->sort()->values();
        $middle = intdiv($count, 2);
        $median = $count % 2 === 0
            ? round(($sorted[$middle - 1] + $sorted[$middle]) / 2, 2)
            : $sorted[$middle];

        $frequencies = array_count_values($scores->map(fn ($s) => (string) $s)->all());
        ksort($frequencies, SORT_NUMERIC);
        arsort($frequencies);

        return [
            'count' => $count,
            'mean' => round($scores->avg(), 2),
            'median' => $median,
            'mode' => (float) array_key_first($frequencies),
            'min' => $scores->min(),
            'max' => $scores->max(),
        ];
    }
}
