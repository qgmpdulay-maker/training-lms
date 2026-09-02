<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingRequest;
use App\Models\TrainingTarget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
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

    // Matches the APB/Technical Assistance legend colors already used on the
    // Calendar tab (bg-blue-400 / bg-orange-400), so the category is
    // recognizable across pages.
    private const CATEGORY_COLORS = [
        TrainingRequest::CATEGORY_APB => '#60a5fa',
        TrainingRequest::CATEGORY_TA => '#fb923c',
    ];

    public function index(Request $request): View
    {
        // This page pulls every training request (plus their participants,
        // evaluations, and instructors) into memory to build the charts and
        // summaries below — with a large dataset that legitimately needs
        // more than PHP's common 128M default. Never lower an already-higher
        // or unlimited (-1) limit set by the host.
        $currentLimit = self::iniMemoryLimitBytes(ini_get('memory_limit'));
        if ($currentLimit !== -1 && $currentLimit < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }

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
            'categoryDonut' => $this->categoryDonut($region),
            'taAccomplishment' => $this->taAccomplishment($region),
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

    /**
     * Super Admin sets the planning target for a Technical Assistance
     * training type — everything else on the accomplishment chart
     * (Accomplished) is derived from actual completed requests, but Target
     * is an externally set planning number with no other source of truth.
     * Targets are per-region (e.g. NCR's target for a title differs from
     * Region III's); a null region is the nationwide "All Regions" target,
     * set the same way while that filter is selected on the Tools page.
     */
    public function updateTarget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_title' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', Rule::in(config('regions.list'))],
            'target' => ['required', 'integer', 'min:0'],
        ]);

        TrainingTarget::updateOrCreate(
            [
                'training_title' => $validated['training_title'],
                'category' => TrainingRequest::CATEGORY_TA,
                'region' => $validated['region'] ?? null,
            ],
            ['target' => $validated['target']],
        );

        $regionLabel = $validated['region'] ?? 'All Regions';

        return back()->with('status', "Target updated for {$validated['training_title']} ({$regionLabel}).");
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

    /**
     * Requests split by category (APB vs. Technical Assistance) — sits next
     * to the status donut as the other natural cut of the same request
     * volume, colored to match the APB/TA legend already used on Calendar.
     */
    private function categoryDonut(?string $region): array
    {
        $total = TrainingRequest::when($region, fn ($query) => $query->where('region', $region))->count();
        $apb = TrainingRequest::where('category', TrainingRequest::CATEGORY_APB)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->count();
        $ta = $total - $apb;

        $radius = 40;
        $circumference = 2 * M_PI * $radius;
        $apbFraction = $total > 0 ? $apb / $total : 0;
        $apbArc = $apbFraction * $circumference;

        return [
            'total' => $total,
            'circumference' => $circumference,
            'radius' => $radius,
            'segments' => [
                [
                    'label' => TrainingRequest::$categoryLabels[TrainingRequest::CATEGORY_APB],
                    'value' => $apb,
                    'percent' => $total > 0 ? round($apbFraction * 100) : 0,
                    'color' => self::CATEGORY_COLORS[TrainingRequest::CATEGORY_APB],
                    'dasharray' => "{$apbArc} {$circumference}",
                    'dashoffset' => 0,
                ],
                [
                    'label' => TrainingRequest::$categoryLabels[TrainingRequest::CATEGORY_TA],
                    'value' => $ta,
                    'percent' => $total > 0 ? 100 - round($apbFraction * 100) : 0,
                    'color' => self::CATEGORY_COLORS[TrainingRequest::CATEGORY_TA],
                    'dasharray' => ($circumference - $apbArc).' '.$circumference,
                    'dashoffset' => -$apbArc,
                ],
            ],
        ];
    }

    /**
     * Target vs. Accomplished per Technical Assistance training type.
     * Accomplished is the graduate count from completed TA requests of that
     * title; Target comes from the separately maintained TrainingTarget
     * table, since it's a planning figure with no other source of truth.
     * Both are scoped to the same region as the rest of the page — a null
     * $region reads/writes the nationwide "All Regions" target, not a sum
     * of every region's target, so switching the region filter shows that
     * region's own target rather than a blended figure.
     * Every title that has either a completed TA request or a target on
     * file gets a row, so a target can be set ahead of the first request.
     *
     * @return array<int, array{title: string, target: int, accomplished: int, percent: int}>
     */
    private function taAccomplishment(?string $region): array
    {
        $accomplished = TrainingRequest::where('category', TrainingRequest::CATEGORY_TA)
            ->where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->with('participants')
            ->get()
            ->groupBy('training_title')
            ->map(fn ($records) => $records->sum(fn (TrainingRequest $r) => max($r->participants->count(), 1)));

        $targets = TrainingTarget::where('category', TrainingRequest::CATEGORY_TA)
            ->where('region', $region)
            ->pluck('target', 'training_title');

        $titles = $accomplished->keys()->merge($targets->keys())->unique()->sort()->values();

        $maxValue = max($accomplished->max() ?? 0, $targets->max() ?? 0, 1);

        return $titles->map(fn ($title) => [
            'title' => $title,
            'target' => $targets[$title] ?? 0,
            'accomplished' => $accomplished[$title] ?? 0,
            'target_percent' => round((($targets[$title] ?? 0) / $maxValue) * 100),
            'accomplished_percent' => round((($accomplished[$title] ?? 0) / $maxValue) * 100),
        ])->keyBy('title')->all();
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

                $trainerScores = $moduleRatings->pluck('trainer_rating')
                    ->merge($participantModuleRatings->pluck('trainer_rating'))
                    ->filter(fn ($r) => is_numeric($r));

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
                    'trainer_ratings_by_module' => $trainerRatingsByModule,
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
     * Parses a php.ini-style memory value ("128M", "1G", "-1") into bytes.
     */
    private static function iniMemoryLimitBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '-1' || $value === '') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
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
