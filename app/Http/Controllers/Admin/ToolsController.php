<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ToolsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isAdmin() ? $user->region : ($user->isSuperAdmin() ? $request->query('region') : null);

        $filesSearch = trim((string) $request->query('files_q'));

        $filesRecords = TrainingRequest::with(['user', 'participants', 'trainingEvaluation'])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($filesSearch !== '', function ($query) use ($filesSearch) {
                $query->where(function ($q) use ($filesSearch) {
                    $q->where('training_title', 'like', "%{$filesSearch}%")
                        ->orWhere('venue', 'like', "%{$filesSearch}%")
                        ->orWhere('lgu', 'like', "%{$filesSearch}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$filesSearch}%"))
                        ->orWhereHas('participants', fn ($p) => $p->where('name', 'like', "%{$filesSearch}%"));
                });
            })
            ->orderByDesc('preferred_date')
            ->paginate(10, ['*'], 'files')
            ->withQueryString();

        // The search box and pagination links re-request this same route and swap
        // in just the table, so typing/paging doesn't reload the whole page — see
        // resources/views/admin/partials/live-search-script.blade.php.
        if ($request->ajax() && $request->query('_section') === 'files') {
            return view('admin.partials.files-table', compact('filesRecords'));
        }

        return view('admin.tools', [
            'region' => $region,
            'regionLocked' => $user->isAdmin(),
            'regions' => config('regions.list'),
            'filesRecords' => $filesRecords,
            'filesSearch' => $filesSearch,
            'evaluationsByTraining' => $this->evaluationSummaries($region),
        ]);
    }

    public function uploadFiles(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);

        $validated = $request->validate([
            'atar_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $previousFile = null;

        if ($request->hasFile('atar_file')) {
            $previousFile = $trainingRequest->atar_file_path;
            $trainingRequest->atar_file_path = $validated['atar_file']->store('atar', 'public');
        }

        $trainingRequest->save();

        if ($previousFile) {
            Storage::disk('public')->delete($previousFile);
        }

        return back()->with('status', "Files updated for {$trainingRequest->training_title}.");
    }

    /**
     * One session's full L1/L2 breakdown, fetched when its row on the
     * Evaluation Computation list is first expanded.
     */
    public function evaluationDetails(Request $request, TrainingRequest $trainingRequest): View
    {
        $user = $request->user();
        abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);

        $trainingRequest->load(['trainingEvaluation', 'participantEvaluations.user:id,name', 'instructors'])
            ->loadCount('participants');

        return view('admin.partials.evaluation-session-details', [
            'session' => $this->sessionSummary($trainingRequest),
        ]);
    }

    public function downloadAtarTemplate(): Response
    {
        return Pdf::loadView('pdf.atar-template')
            ->setPaper('a4', 'portrait')
            ->download('after-training-activity-report-template.pdf');
    }

    public function downloadCertificateTemplate(): Response
    {
        return Pdf::loadView('pdf.certificate-template')
            ->setPaper('a4', 'landscape')
            ->download('training-certificate-template.pdf');
    }

    /**
     * Evaluated sessions grouped by training title (one tab per title on the
     * Tools page), each session listed separately underneath — every session
     * gets exactly one admin-entered evaluation via "Add Evaluation", so
     * pooling several sessions of the same title into shared stats mixed
     * unrelated runs' pre/post-test scores into meaningless combined
     * figures. A session appears here if it has an admin evaluation, one or
     * more participant evaluations, or both — module and instructor ratings
     * are shown side by side from each source rather than merged into one
     * number, since they measure different things (the admin's own
     * assessment vs. what participants reported).
     *
     * @return array<string, Collection>
     */
    private function evaluationSummaries(?string $region): array
    {
        return TrainingRequest::where(fn ($query) => $query->whereHas('trainingEvaluation')->orWhereHas('participantEvaluations'))
            ->when($region, fn ($query) => $query->where('region', $region))
            // Only what the collapsed session rows show — each session's full
            // breakdown is built on demand by evaluationDetails().
            ->with(['trainingEvaluation', 'participantEvaluations:id,training_request_id,module_ratings,updated_at'])
            ->withCount('participants')
            ->orderByDesc('preferred_date')
            ->get()
            ->map(fn (TrainingRequest $trainingRequest) => $this->sessionHeader($trainingRequest))
            ->groupBy('training_title')
            ->sortKeys()
            ->all();
    }

    /**
     * The fields shown on a session's collapsed row. Expects trainingEvaluation
     * and participantEvaluations loaded, plus participants_count.
     *
     * @return array<string, mixed>
     */
    private function sessionHeader(TrainingRequest $trainingRequest): array
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
    private function sessionSummary(TrainingRequest $trainingRequest): array
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
            ...$this->sessionHeader($trainingRequest),
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
