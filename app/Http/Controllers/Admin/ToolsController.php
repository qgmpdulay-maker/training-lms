<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ToolsController extends Controller
{
    // Status-semantic colors from the design system's fixed status palette,
    // plus one categorical slot (blue) and one neutral for the two statuses
    // that aren't strictly good/warning/critical.
    private const STATUS_COLORS = [
        TrainingRequest::STATUS_SUBMITTED => '#9ca3af',
        TrainingRequest::STATUS_UNDER_REVIEW => '#fab219',
        TrainingRequest::STATUS_APPROVED => '#2a78d6',
        TrainingRequest::STATUS_DECLINED => '#d03b3b',
        TrainingRequest::STATUS_COMPLETED => '#0ca30c',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $region = $user->isAdmin() ? $user->region : ($user->isSuperAdmin() ? $request->query('region') : null);

        $filesRecords = TrainingRequest::with(['user', 'participants', 'trainingEvaluation'])
            ->when($region, fn ($query) => $query->where('region', $region))
            ->orderByDesc('preferred_date')
            ->paginate(10, ['*'], 'files')
            ->withQueryString();

        // The files table paginates via plain links; fetch() re-requests this same
        // route and swaps in just the table so paging doesn't reload the whole page.
        if ($request->ajax()) {
            return view('admin.partials.files-table', compact('filesRecords'));
        }

        $graduatesByTraining = TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('participants')
            ->get()
            ->groupBy('training_title')
            ->map(fn ($records) => [
                'total' => $records->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)),
                'byYear' => $records->groupBy(fn (TrainingRequest $r) => $r->preferred_date->format('Y'))
                    ->map(fn ($yearRecords) => $yearRecords->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)))
                    ->sortKeys(),
            ])
            ->sortKeys();

        return view('admin.tools', [
            'region' => $region,
            'regionLocked' => $user->isAdmin(),
            'regions' => config('regions.list'),
            'graduatesByTraining' => $graduatesByTraining,
            'filesRecords' => $filesRecords,
            'statusDonut' => $this->statusDonut($region),
            'statusBars' => $this->statusBars($region),
            'evaluationsByTraining' => $this->evaluationSummaries($region),
            'graduatesByLgu' => $this->graduatesByLgu($region),
        ]);
    }

    public function uploadFiles(Request $request, TrainingRequest $trainingRequest): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->isAdmin() && $trainingRequest->region !== $user->region, 403);

        $validated = $request->validate([
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'atar_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        if ($request->hasFile('certificate_file')) {
            $trainingRequest->certificate_file_path = $validated['certificate_file']->store('certificates', 'public');
        }

        if ($request->hasFile('atar_file')) {
            $trainingRequest->atar_file_path = $validated['atar_file']->store('atar', 'public');
        }

        $trainingRequest->save();

        return back()->with('status', "Files updated for {$trainingRequest->training_title}.");
    }

    public function downloadAtarTemplate(): \Symfony\Component\HttpFoundation\Response
    {
        return Pdf::loadView('pdf.atar-template')
            ->setPaper('a4', 'portrait')
            ->download('after-training-activity-report-template.pdf');
    }

    public function downloadCertificateTemplate(): \Symfony\Component\HttpFoundation\Response
    {
        return Pdf::loadView('pdf.certificate-template')
            ->setPaper('a4', 'landscape')
            ->download('training-certificate-template.pdf');
    }

    private function statusDonut(?string $region): array
    {
        $total = TrainingRequest::when($region, fn ($query) => $query->where('region', $region))->count();
        $accomplished = TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->count();
        $pending = $total - $accomplished;

        $radius = 40;
        $circumference = 2 * M_PI * $radius;
        $accomplishedFraction = $total > 0 ? $accomplished / $total : 0;
        $accomplishedArc = $accomplishedFraction * $circumference;

        return [
            'total' => $total,
            'circumference' => $circumference,
            'radius' => $radius,
            'segments' => [
                [
                    'label' => 'Accomplished',
                    'value' => $accomplished,
                    'percent' => $total > 0 ? round($accomplishedFraction * 100) : 0,
                    'color' => self::STATUS_COLORS[TrainingRequest::STATUS_COMPLETED],
                    'dasharray' => "{$accomplishedArc} {$circumference}",
                    'dashoffset' => 0,
                ],
                [
                    'label' => 'Pending',
                    'value' => $pending,
                    'percent' => $total > 0 ? 100 - round($accomplishedFraction * 100) : 0,
                    'color' => '#9ca3af',
                    'dasharray' => ($circumference - $accomplishedArc).' '.$circumference,
                    'dashoffset' => -$accomplishedArc,
                ],
            ],
        ];
    }

    private function statusBars(?string $region): array
    {
        $counts = TrainingRequest::selectRaw('status, count(*) as count')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->groupBy('status')
            ->pluck('count', 'status');

        $maxCount = max($counts->max(), 1);

        return collect(TrainingRequest::$statusLabels)
            ->map(fn ($label, $status) => [
                'label' => $label,
                'value' => $counts[$status] ?? 0,
                'percent' => round((($counts[$status] ?? 0) / $maxCount) * 100),
                'color' => self::STATUS_COLORS[$status],
            ])
            ->values()
            ->all();
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
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function evaluationSummaries(?string $region): array
    {
        return TrainingRequest::where(fn ($query) => $query->whereHas('trainingEvaluation')->orWhereHas('participantEvaluations'))
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with(['trainingEvaluation', 'participantEvaluations.user', 'instructors', 'participants'])
            ->orderByDesc('preferred_date')
            ->get()
            ->map(function (TrainingRequest $trainingRequest) {
                $evaluation = $trainingRequest->trainingEvaluation;
                $moduleRatings = collect($evaluation->module_ratings ?? []);
                $participantModuleRatings = $trainingRequest->participantEvaluations->pluck('module_ratings')->filter()->flatten(1);
                $participantInstructorRatings = $trainingRequest->participantEvaluations->pluck('instructor_ratings')->filter()->flatten(1);

                $modules = $moduleRatings->pluck('module')
                    ->merge($participantModuleRatings->pluck('module'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->map(function ($moduleName) use ($moduleRatings, $participantModuleRatings) {
                        $adminRows = $moduleRatings->where('module', $moduleName);
                        $participantRows = $participantModuleRatings->where('module', $moduleName);

                        $moduleScores = $adminRows->pluck('module_rating')->filter(fn ($r) => is_numeric($r));
                        $trainerScores = $adminRows->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));
                        $participantScores = $participantRows->pluck('module_rating')->filter(fn ($r) => is_numeric($r));

                        return [
                            'module' => $moduleName,
                            'module_rating' => $moduleScores->isNotEmpty() ? round($moduleScores->avg(), 2) : null,
                            'trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
                            'participant_rating' => $participantScores->isNotEmpty() ? round($participantScores->avg(), 2) : null,
                            'participant_responses' => $participantScores->count(),
                            'rating_distribution' => $this->ratingDistribution($participantScores),
                            'comments' => $participantRows->pluck('comment')->filter(fn ($c) => filled(trim((string) $c)))->values()->all(),
                        ];
                    });

                $trainerScores = $moduleRatings->pluck('trainer_rating')->filter(fn ($r) => is_numeric($r));

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

                    return [
                        'participant' => $participantEvaluation->user?->name ?? 'Unknown participant',
                        'scores' => $moduleMatrixModules->mapWithKeys(fn ($module) => [$module => $ratingsByModule[$module]['module_rating'] ?? null])->all(),
                    ];
                })->values();

                return [
                    'training_request_id' => $trainingRequest->id,
                    'training_title' => $trainingRequest->training_title,
                    'preferred_date' => $trainingRequest->preferred_date,
                    'venue' => $trainingRequest->venue,
                    'updated_at' => collect([$evaluation?->updated_at, $trainingRequest->participantEvaluations->max('updated_at')])->filter()->max(),
                    'modules' => $modules,
                    'overall_trainer_rating' => $trainerScores->isNotEmpty() ? round($trainerScores->avg(), 2) : null,
                    'pretest_stats' => $this->scoreStatistics($pretestScores),
                    'posttest_stats' => $this->scoreStatistics($posttestScores),
                    'instructor_ratings' => $instructorRatings,
                    'participant_response_count' => $trainingRequest->participantEvaluations->count(),
                    'participant_total' => $trainingRequest->effectiveParticipants()->count(),
                    'module_matrix_columns' => $moduleMatrixModules->all(),
                    'module_matrix' => $moduleMatrix,
                ];
            })
            ->groupBy('training_title')
            ->sortKeys()
            ->all();
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

    /**
     * Graduates by LGU, grouped by OCD region — when a super admin isn't
     * scoped to one region, LGUs from different regions can share a name,
     * so a single flat ranking would blur them together. Keyed by region
     * title to match evaluationsByTraining's shape, since the Tools view
     * uses the same tab-switcher pattern for both.
     *
     * @return array<string, array{total: int, lgus: array}>
     */
    private function graduatesByLgu(?string $region): array
    {
        return TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->whereNotNull('lgu')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('participants')
            ->get()
            ->groupBy(fn (TrainingRequest $r) => $r->region ?: __('Unspecified Region'))
            ->map(function ($records) {
                $lgus = $records->groupBy('lgu')
                    ->map(fn ($lguRecords, $lgu) => [
                        'lgu' => $lgu,
                        'total' => $lguRecords->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)),
                    ])
                    ->sortByDesc('total')
                    ->values()
                    ->all();

                return [
                    'total' => array_sum(array_column($lgus, 'total')),
                    'lgus' => $lgus,
                ];
            })
            ->sortByDesc('total')
            ->all();
    }
}
