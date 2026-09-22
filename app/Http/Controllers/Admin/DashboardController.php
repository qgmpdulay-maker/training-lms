<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\SuperAdmin\MonitoringController;
use App\Http\Controllers\Controller;
use App\Models\AtarRecord;
use App\Models\Instructor;
use App\Models\TrainingNeedsAssessment;
use App\Models\TrainingRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        // Defaults to the current year so the chart opens on the most relevant
        // slice of data rather than every year stacked together; "All Years"
        // is still one filter away for anyone who wants the full history. Only
        // this one chart is year-scoped — everything else on the dashboard
        // stays all-time.
        $year = $request->query('year', (string) now()->year);

        if (! $user->isSuperAdmin()) {
            // Loaded once and reused by every chart below (year-filtered in
            // memory, not re-queried). withCount adds a participants_count
            // number per training instead of loading each participant's row.
            $completed = TrainingRequest::completed()->where('region', $user->region)->withCount('participants')->get();

            $availableYears = $this->availableYears($user->region);
            $graduatesByTraining = $this->graduatesByTrainingChart($this->inYear($completed, $year));
            $requestsByTraining = $this->requestsByTrainingChart($user->region);
            $statusBreakdown = $this->statusBreakdownChart($user->region);
            $graduatesBySex = [
                'male' => $completed->sum('graduates_male'),
                'female' => $completed->sum('graduates_female'),
            ];
            $graduatesByAgeRange = $this->graduatesByAgeRangeChart($completed);
            $mostNeededTrainings = $this->mostNeededTrainingsChart($user->region);
            $atarTrainingsByMode = $this->atarTrainingsByModeChart($user->region);
            $atarTrainingsByMonth = $this->atarTrainingsByMonthChart($user->region);
            $atarGraduatesBySector = $this->atarGraduatesBySectorChart($user->region);
            $participantsByCity = $this->participantsByCityChart($user->region);

            return view('admin.dashboard-regional', [
                'user' => $user,
                'year' => $year,
                'availableYears' => $availableYears,
                'region' => $user->region,
                'graduatesByLgu' => $this->graduatesByLgu($completed),
                'stats' => [
                    'instructors' => Instructor::where('region', $user->region)->count(),
                    'tna_submissions' => TrainingNeedsAssessment::whereHas('user', fn ($q) => $q->where('region', $user->region))->count(),
                    'upcoming_trainings' => TrainingRequest::where('region', $user->region)
                        ->where('preferred_date', '>=', today())
                        ->whereIn('status', [TrainingRequest::STATUS_APPROVED, TrainingRequest::STATUS_UNDER_REVIEW])
                        ->count(),
                ],
                'chartData' => [
                    'graduatesByTraining' => $graduatesByTraining,
                    'requestsByTraining' => $requestsByTraining,
                    'statusBreakdown' => $statusBreakdown,
                    'graduatesBySex' => $graduatesBySex,
                    'graduatesByAgeRange' => $graduatesByAgeRange,
                    'mostNeededTrainings' => $mostNeededTrainings,
                    'atarTrainingsByMode' => $atarTrainingsByMode,
                    'atarTrainingsByMonth' => $atarTrainingsByMonth,
                    'atarGraduatesBySector' => $atarGraduatesBySector,
                    'participantsByCity' => $participantsByCity,
                ],
                'graduatesByLocation' => MonitoringController::mapPoints($completed),
                'regionCenter' => config('regions.geo.'.$user->region, [12.8797, 121.7740]),
                'insights' => $this->buildInsights(
                    statusBreakdown: $statusBreakdown,
                    graduatesByTraining: $graduatesByTraining,
                    requestsByTraining: $requestsByTraining,
                    graduatesByAgeRange: $graduatesByAgeRange,
                    graduatesBySex: $graduatesBySex,
                    mostNeededTrainings: $mostNeededTrainings,
                    lowestCompletionRateTraining: $this->lowestCompletionRateTraining($user->region),
                    lguNgaReach: $this->lguNgaReach($completed),
                ),
            ]);
        }

        // Every completed training nationwide, loaded once and reused by the
        // charts below (filtered in memory by region/year rather than
        // re-queried). withCount adds a participants_count number per
        // training instead of loading each participant's full row.
        $completed = TrainingRequest::completed()->withCount('participants')->get();

        // One shared region filter for the five overview charts below, also
        // reused by $monitoringFilters['regions'] further down for the
        // Regional Performance & Graduates Map section — a single control
        // for both rather than two independent region filters on one page.
        $chartRegion = $request->query('chart_region') ?: null;
        $completedForCharts = $completed->when($chartRegion, fn ($c) => $c->where('region', $chartRegion));

        $availableYears = $this->availableYears();
        $graduatesByTraining = $this->graduatesByTrainingChart($this->inYear($completedForCharts, $year));
        $requestsByTraining = $this->requestsByTrainingChart();
        $statusBreakdown = $this->statusBreakdownChart($chartRegion);
        $graduatesByRegion = $this->graduatesByRegionChart($completed);
        $graduatesBySex = [
            'male' => $completedForCharts->sum('graduates_male'),
            'female' => $completedForCharts->sum('graduates_female'),
        ];
        $graduatesByAgeRange = $this->graduatesByAgeRangeChart($completedForCharts);
        $mostNeededTrainings = $this->mostNeededTrainingsChart($chartRegion);
        $requestedVsAccomplished = $this->requestedVsAccomplishedChart($chartRegion, $year);
        $categoryComparison = $this->categoryComparisonChart($chartRegion);
        $threeYearTrend = $this->threeYearTrendChart($chartRegion);

        // Regional Monitoring and the Graduates Map used to be their own tabs —
        // folded into the dashboard so Super Admin has one place to look,
        // sharing the same region filter as the charts above (previously its
        // own independent multi-select).
        $monitoringFilters = [
            'regions' => $chartRegion ? [$chartRegion] : [],
            'training_title' => $request->query('training_title') ?: null,
            'from' => $request->query('from') ?: null,
            'until' => $request->query('until') ?: null,
        ];
        // The Regional Performance table reuses these same filtered trainings
        // instead of running the identical query a second time.
        $monitoringTrainings = MonitoringController::completedTrainings($monitoringFilters)->get();
        $regionalData = MonitoringController::regionalData($monitoringTrainings);

        // Needs Assessment used to show its own "What Training Is Needed" chart
        // and a per-organization breakdown table — the chart duplicated
        // mostNeededTrainingsChart() above, so only the breakdown table (which
        // has no dashboard equivalent) moved over.
        $needsAssessmentByOrganization = $this->needsAssessmentByOrganization($request);

        $chartData = [
            'graduatesByTraining' => $graduatesByTraining,
            'requestsByTraining' => $requestsByTraining,
            'statusBreakdown' => $statusBreakdown,
            'graduatesBySex' => $graduatesBySex,
            'graduatesByAgeRange' => $graduatesByAgeRange,
            'mostNeededTrainings' => $mostNeededTrainings,
            'requestedVsAccomplished' => $requestedVsAccomplished,
            'categoryComparison' => $categoryComparison,
            'threeYearTrend' => $threeYearTrend,
        ];
        $insights = $this->buildInsights(
            statusBreakdown: $statusBreakdown,
            graduatesByTraining: $graduatesByTraining,
            requestsByTraining: $requestsByTraining,
            graduatesByAgeRange: $graduatesByAgeRange,
            graduatesByRegion: $graduatesByRegion,
            graduatesBySex: $graduatesBySex,
            mostNeededTrainings: $mostNeededTrainings,
            lowestCompletionRateTraining: $this->lowestCompletionRateTraining($chartRegion),
            lguNgaReach: $this->lguNgaReach($completedForCharts),
        );
        $regionsList = config('regions.list');
        $trainingTitles = collect(config('trainings.catalog'))->pluck('title');
        $monitoringSummary = MonitoringController::summary($monitoringTrainings);
        $regionalHighlights = MonitoringController::regionalHighlights($regionalData);
        $mapPoints = MonitoringController::mapPoints($monitoringTrainings);

        // Every filter control on the dashboard (chart region, year, and the
        // Regional Performance filters) submits via fetch() instead of a plain
        // GET navigation, so filtering doesn't reload the page or reset scroll
        // position — see submitDashboardFilter() in dashboard.blade.php. This
        // returns just the re-rendered fragments instead of the full page.
        if ($request->ajax()) {
            return response()->json([
                'charts_html' => view('admin.super-admin.partials.dashboard-charts', [
                    'chartRegion' => $chartRegion,
                    'regions' => $regionsList,
                    'year' => $year,
                    'availableYears' => $availableYears,
                    'chartData' => $chartData,
                    'monitoringFilters' => $monitoringFilters,
                ])->render(),
                'insights_html' => view('admin.super-admin.partials.dashboard-insights', [
                    'insights' => $insights,
                ])->render(),
                'monitoring_html' => view('admin.super-admin.partials.dashboard-monitoring', [
                    'chartRegion' => $chartRegion,
                    'monitoringFilters' => $monitoringFilters,
                    'regions' => $regionsList,
                    'trainingTitles' => $trainingTitles,
                    'monitoringSummary' => $monitoringSummary,
                    'regionalHighlights' => $regionalHighlights,
                    'regionalData' => $regionalData,
                    'mapPoints' => $mapPoints,
                ])->render(),
                'chartData' => $chartData,
                'mapPoints' => $mapPoints,
            ]);
        }

        return view('admin.super-admin.dashboard', [
            'user' => $user,
            'year' => $year,
            'availableYears' => $availableYears,
            'chartRegion' => $chartRegion,
            'stats' => [
                'instructors' => Instructor::count(),
                'tna_submissions' => TrainingNeedsAssessment::count(),
                'upcoming_trainings' => TrainingRequest::where('preferred_date', '>=', today())
                    ->whereIn('status', [TrainingRequest::STATUS_APPROVED, TrainingRequest::STATUS_UNDER_REVIEW])
                    ->count(),
            ],
            'chartData' => $chartData,
            'insights' => $insights,
            'monitoringFilters' => $monitoringFilters,
            'regions' => $regionsList,
            'trainingTitles' => $trainingTitles,
            'monitoringSummary' => $monitoringSummary,
            'regionalData' => $regionalData,
            'regionalHighlights' => $regionalHighlights,
            'mapPoints' => $mapPoints,
            'needsAssessmentByOrganization' => $needsAssessmentByOrganization,
            'region' => null,
            'graduatesByLgu' => $this->graduatesByLgu($completed),
        ]);
    }

    /**
     * Plain-language callouts synthesized from the chart data above — the
     * "so what" a Super Admin or Regional Admin can read in a few seconds
     * without having to mentally compare bars themselves. Only speaks to
     * whichever breakdowns are actually passed in, so the same method serves
     * both dashboards despite their charts differing (region vs sex).
     *
     * @return array<int, string>
     */
    private function buildInsights(
        array $statusBreakdown,
        array $graduatesByTraining,
        array $requestsByTraining,
        array $graduatesByAgeRange,
        ?array $graduatesByRegion = null,
        ?array $graduatesBySex = null,
        ?array $mostNeededTrainings = null,
        ?array $lowestCompletionRateTraining = null,
        ?array $lguNgaReach = null,
    ): array {
        $insights = [];

        $totalRequests = collect($statusBreakdown)->sum('value');
        $completed = collect($statusBreakdown)->firstWhere('label', TrainingRequest::$statusLabels[TrainingRequest::STATUS_COMPLETED])['value'] ?? 0;
        $pending = collect($statusBreakdown)
            ->whereIn('label', [
                TrainingRequest::$statusLabels[TrainingRequest::STATUS_SUBMITTED],
                TrainingRequest::$statusLabels[TrainingRequest::STATUS_UNDER_REVIEW],
            ])
            ->sum('value');

        if ($totalRequests > 0) {
            $rate = round($completed / $totalRequests * 100);
            $insights[] = __(':rate% of all training requests on file have been completed (:completed of :total).', [
                'rate' => $rate, 'completed' => $completed, 'total' => $totalRequests,
            ]);
        }

        if ($pending > 0) {
            $insights[] = trans_choice(
                ':count training request is still awaiting review or action.|:count training requests are still awaiting review or action.',
                $pending,
                ['count' => $pending]
            );
        }

        // Unlike the insights above and below, which mostly restate whichever
        // chart's top bar is already visible on the page, this one is a
        // "watch list" callout — something that actually needs attention
        // rather than a fact already obvious from the charts.
        if ($lowestCompletionRateTraining !== null) {
            $insights[] = __(':training has the lowest completion rate among trainings with multiple requests on file (:rate%, :completed of :total) — may be worth a closer look.', [
                'training' => $lowestCompletionRateTraining['training'],
                'rate' => $lowestCompletionRateTraining['rate'],
                'completed' => $lowestCompletionRateTraining['completed'],
                'total' => $lowestCompletionRateTraining['total'],
            ]);
        }

        if (! empty($requestsByTraining)) {
            $top = $requestsByTraining[0];
            $insights[] = __(':training is the most requested training, with :count requests on file.', [
                'training' => $top['training'], 'count' => $top['requests'],
            ]);
        }

        if (! empty($graduatesByTraining)) {
            $top = $graduatesByTraining[0];
            $insights[] = __(':training has produced the most graduates so far, with :count completions.', [
                'training' => $top['training'], 'count' => $top['graduates'],
            ]);
        }

        $ageLabels = [
            'age_18_30' => '18–30', 'age_31_45' => '31–45', 'age_46_59' => '46–59', 'age_60_up' => '60 and above',
        ];
        $ageTotal = array_sum($graduatesByAgeRange);
        if ($ageTotal > 0) {
            $topAgeKey = array_search(max($graduatesByAgeRange), $graduatesByAgeRange);
            $percent = round($graduatesByAgeRange[$topAgeKey] / $ageTotal * 100);
            $insights[] = __('Most graduates fall in the :range age range (:percent%).', [
                'range' => $ageLabels[$topAgeKey], 'percent' => $percent,
            ]);
        }

        if (! empty($graduatesByRegion)) {
            $top = $graduatesByRegion[0];
            $insights[] = __(':region leads in graduates produced, with :count so far.', [
                'region' => $top['region'], 'count' => $top['graduates'],
            ]);

            // Only meaningful nationwide (this whole callout is skipped for
            // the regional dashboard, since $graduatesByRegion is only ever
            // passed on the Super Admin side) — a coverage gap, not just a
            // leaderboard, since graduatesByRegionChart() already drops any
            // region with zero graduates rather than listing it at zero.
            $regionsWithNoGraduates = count(config('regions.list')) - count($graduatesByRegion);
            if ($regionsWithNoGraduates > 0) {
                $insights[] = trans_choice(
                    ':count region has no completed-training graduates on file yet.|:count regions have no completed-training graduates on file yet.',
                    $regionsWithNoGraduates,
                    ['count' => $regionsWithNoGraduates]
                );
            }
        }

        if (! empty($mostNeededTrainings)) {
            $top = $mostNeededTrainings[0];
            $insights[] = __(':training is the most needed training per the Training Needs Assessment, recommended :count times.', [
                'training' => $top['training'], 'count' => $top['count'],
            ]);
        }

        if ($graduatesBySex !== null) {
            $sexTotal = $graduatesBySex['male'] + $graduatesBySex['female'];
            if ($sexTotal > 0) {
                $majorityIsMale = $graduatesBySex['male'] >= $graduatesBySex['female'];
                $percent = round(max($graduatesBySex['male'], $graduatesBySex['female']) / $sexTotal * 100);
                $insights[] = $majorityIsMale
                    ? __('Most graduates are male (:percent%).', ['percent' => $percent])
                    : __('Most graduates are female (:percent%).', ['percent' => $percent]);
            }
        }

        if ($lguNgaReach !== null && ($lguNgaReach['lgus'] + $lguNgaReach['ngas']) > 0) {
            $insights[] = __('Completed trainings have reached :lgus and :ngas so far.', [
                'lgus' => trans_choice(':count LGU|:count LGUs', $lguNgaReach['lgus'], ['count' => $lguNgaReach['lgus']]),
                'ngas' => trans_choice(':count NGA|:count NGAs', $lguNgaReach['ngas'], ['count' => $lguNgaReach['ngas']]),
            ]);
        }

        return $insights;
    }

    /**
     * The single worst completion rate among trainings with enough requests
     * on file to mean something (>= 3) — an actionable "watch list" signal,
     * unlike the rest of buildInsights()'s callouts, which mostly just
     * restate whichever chart's top bar is already visible above them.
     * Returns null if nothing qualifies (too few requests per training, or
     * every qualifying training is already completing at a healthy rate).
     *
     * @return array{training: string, rate: int, completed: int, total: int}|null
     */
    private function lowestCompletionRateTraining(?string $region): ?array
    {
        $rows = TrainingRequest::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw('training_title, count(*) as total, sum(case when status = ? then 1 else 0 end) as completed', [TrainingRequest::STATUS_COMPLETED])
            ->groupBy('training_title')
            ->havingRaw('count(*) >= 3')
            ->get()
            ->map(fn ($row) => [
                'training' => $row->training_title,
                'total' => (int) $row->total,
                'completed' => (int) $row->completed,
                'rate' => (int) round(((int) $row->completed) / ((int) $row->total) * 100),
            ]);

        $lowest = $rows->sortBy('rate')->first();

        // Only worth flagging if it's genuinely lagging — otherwise every
        // dashboard load would surface some training as "the worst" even
        // when everything is completing reasonably well.
        return ($lowest && $lowest['rate'] < 50) ? $lowest : null;
    }

    /**
     * Distinct LGUs/NGAs actually reached by completed trainings — the same
     * agency_type split MonitoringController::summary() computes for the
     * Regional Performance table, recomputed here from the same $completed
     * collection the rest of buildInsights() already uses (rather than
     * reusing $monitoringSummary directly), so this number doesn't silently
     * change when someone adjusts the separate Regional Performance filters.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     * @return array{lgus: int, ngas: int}
     */
    private function lguNgaReach(Collection $completed): array
    {
        return [
            'lgus' => $completed->where('agency_type', TrainingRequest::AGENCY_TYPE_LGU)->pluck('requesting_agency')->filter()->unique()->count(),
            'ngas' => $completed->where('agency_type', TrainingRequest::AGENCY_TYPE_NGA)->pluck('requesting_agency')->filter()->unique()->count(),
        ];
    }

    /**
     * Narrows already-loaded completed trainings to one year in memory, so the
     * year-scoped chart doesn't re-query rows the dashboard already holds.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     */
    private function inYear(Collection $completed, string $year): Collection
    {
        return $year === 'all'
            ? $completed
            : $completed->filter(fn (TrainingRequest $t) => $t->preferred_date?->year === (int) $year);
    }

    /**
     * Every year with at least one completed training on file (region-scoped
     * when given), plus the current year so it's always selectable even
     * before any :year data exists — feeds the Graduates by Training year
     * picker.
     */
    private function availableYears(?string $region = null): Collection
    {
        return TrainingRequest::where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($query) => $query->where('region', $region))
            ->selectRaw('DISTINCT YEAR(preferred_date) as year')
            ->pluck('year')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Graduate totals per training course, completed trainings only — every
     * course in the catalog is Technical Assistance, so this is the CDTI
     * staff's actual training mix rather than an APB/TA split.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     */
    private function graduatesByTrainingChart(Collection $completed): array
    {
        return $completed
            ->groupBy('training_title')
            ->map(fn (Collection $rows, string $title) => [
                'training' => $title,
                'graduates' => $rows->sum(fn (TrainingRequest $t) => $t->graduates),
            ])
            ->filter(fn (array $row) => $row['graduates'] > 0)
            ->sortByDesc('graduates')
            ->values()
            ->all();
    }

    /**
     * Training request counts per training course — every request on file
     * regardless of status, scoped to a single region when one is given.
     */
    private function requestsByTrainingChart(?string $region = null): array
    {
        return TrainingRequest::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw('training_title, count(*) as total')
            ->groupBy('training_title')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['training' => $row->training_title, 'requests' => (int) $row->total])
            ->all();
    }

    /**
     * All-time request counts by status — the pipeline from newly submitted
     * through completed. Scoped to a single region when one is given, otherwise
     * every training request on file.
     */
    private function statusBreakdownChart(?string $region = null): array
    {
        $counts = TrainingRequest::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(TrainingRequest::$statusLabels)
            ->map(fn (string $label, string $status) => [
                'label' => $label,
                'value' => (int) $counts->get($status, 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Graduate totals per age bracket, completed trainings only.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     */
    /**
     * Cumulative Requested vs Accomplished for TA trainings across one year.
     *
     * Deliberately not a target-vs-actual: Technical Assistance has no targets
     * set for it, so the honest comparison is how many were asked for against
     * how many were actually delivered, accumulating month by month. Both
     * series run on preferred_date, so a training counts in the month it was
     * scheduled for rather than the month it was filed.
     *
     * @return array<int, array{month: string, requested: int, accomplished: int}>
     */
    private function requestedVsAccomplishedChart(?string $region, string|int $year): array
    {
        $rows = TrainingRequest::query()
            ->where('category', TrainingRequest::CATEGORY_TA)
            ->when($region, fn ($q) => $q->where('region', $region))
            ->when($year !== 'all', fn ($q) => $q->whereYear('preferred_date', $year))
            ->selectRaw('MONTH(preferred_date) as month_number, count(*) as requested, sum(case when status = ? then 1 else 0 end) as accomplished', [TrainingRequest::STATUS_COMPLETED])
            ->groupBy('month_number')
            ->pluck('requested', 'month_number');

        $accomplishedRows = TrainingRequest::query()
            ->where('category', TrainingRequest::CATEGORY_TA)
            ->where('status', TrainingRequest::STATUS_COMPLETED)
            ->when($region, fn ($q) => $q->where('region', $region))
            ->when($year !== 'all', fn ($q) => $q->whereYear('preferred_date', $year))
            ->selectRaw('MONTH(preferred_date) as month_number, count(*) as total')
            ->groupBy('month_number')
            ->pluck('total', 'month_number');

        $runningRequested = 0;
        $runningAccomplished = 0;

        return collect(range(1, 12))
            ->map(function (int $month) use ($rows, $accomplishedRows, &$runningRequested, &$runningAccomplished) {
                $runningRequested += (int) $rows->get($month, 0);
                $runningAccomplished += (int) $accomplishedRows->get($month, 0);

                return [
                    'month' => Carbon::create(null, $month)->format('M'),
                    'requested' => $runningRequested,
                    'accomplished' => $runningAccomplished,
                ];
            })
            ->all();
    }

    /**
     * APB against TA on the two measures that matter: how many trainings were
     * run, and how many people graduated from them.
     *
     * @return array<int, array{label: string, trainings: int, graduates: int}>
     */
    private function categoryComparisonChart(?string $region): array
    {
        // Aliased graduates_total, not graduates: TrainingRequest has a
        // `graduates` accessor, which would shadow the alias and resolve to 0
        // because the columns it adds up aren't selected here.
        $rows = TrainingRequest::completed()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw('category, count(*) as trainings, sum(graduates_male + graduates_female) as graduates_total')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        return collect(TrainingRequest::$categoryLabels)
            ->map(fn (string $label, string $category) => [
                'label' => $label,
                'trainings' => (int) ($rows[$category]->trainings ?? 0),
                'graduates' => (int) ($rows[$category]->graduates_total ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Graduates per training over the last three years, so a course that is
     * quietly tailing off is easy to spot.
     *
     * Returns every training rather than a top slice: the chart shows one at a
     * time, picked from a dropdown, so all of them need to be available to the
     * browser. Ordered by three-year total, which makes the busiest training
     * the sensible default selection.
     *
     * @return array{years: array<int, int>, trainings: array<int, array{training: string, graduates: array<int, int>}>}
     */
    private function threeYearTrendChart(?string $region): array
    {
        $years = collect(range(now()->year - 2, now()->year));

        $rows = TrainingRequest::completed()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->whereYear('preferred_date', '>=', $years->first())
            ->selectRaw('training_title, YEAR(preferred_date) as year, sum(graduates_male + graduates_female) as graduates_total')
            ->groupBy('training_title', 'year')
            ->get();

        $byTraining = $rows->groupBy('training_title')
            ->map(fn (Collection $group) => $years
                ->map(fn (int $year) => (int) ($group->firstWhere('year', $year)->graduates_total ?? 0))
                ->all())
            ->sortByDesc(fn (array $graduates) => array_sum($graduates));

        return [
            'years' => $years->all(),
            'trainings' => $byTraining
                ->map(fn (array $graduates, string $training) => [
                    'training' => $training,
                    'graduates' => $graduates,
                ])
                ->values()
                ->all(),
        ];
    }

    private function graduatesByAgeRangeChart(Collection $completed): array
    {
        return [
            'age_18_30' => $completed->sum('graduates_age_18_30'),
            'age_31_45' => $completed->sum('graduates_age_31_45'),
            'age_46_59' => $completed->sum('graduates_age_46_59'),
            'age_60_up' => $completed->sum('graduates_age_60_up'),
        ];
    }

    /**
     * What the Training Needs Assessment says participants need most — how
     * many submissions recommended each training, ranked highest-demand
     * first. Scoped to a single region (by the submitting participant's
     * region) when one is given, otherwise every submission on file.
     */
    private function mostNeededTrainingsChart(?string $region = null): array
    {
        return TrainingNeedsAssessment::query()
            ->when($region, fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('region', $region)))
            ->whereNotNull('recommended_training_title')
            ->selectRaw('recommended_training_title, count(*) as total')
            ->groupBy('recommended_training_title')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['training' => $row->recommended_training_title, 'count' => (int) $row->total])
            ->all();
    }

    /**
     * Graduate totals per region, completed trainings only, regions with zero
     * graduates omitted so the chart doesn't pad out with empty bars.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     */
    private function graduatesByRegionChart(Collection $completed): array
    {
        return collect(config('regions.list'))
            ->map(fn (string $region) => [
                'region' => $region,
                'graduates' => $completed->where('region', $region)->sum(fn (TrainingRequest $t) => $t->graduates),
            ])
            ->filter(fn (array $row) => $row['graduates'] > 0)
            ->sortByDesc('graduates')
            ->values()
            ->all();
    }

    /**
     * TNA demand grouped by the participant's LGU/organization, per the TOR
     * requirement to generate needs-assessment data per LGU/organization —
     * moved here from the Needs Assessment tab, which had no dashboard
     * equivalent for this specific breakdown.
     */
    private function needsAssessmentByOrganization(Request $request): LengthAwarePaginator
    {
        // Skips the large answers/category_scores JSON columns — only the
        // recommendation and the submitter's organization/region are used.
        $submissions = TrainingNeedsAssessment::select(['id', 'user_id', 'recommended_training_title'])
            ->with('user:id,organization,region')
            ->get();

        $rows = $submissions
            ->groupBy(fn (TrainingNeedsAssessment $tna) => $tna->user->organization ?: 'Unspecified organization')
            ->map(function ($rows, $organization) {
                $topTraining = $rows->pluck('recommended_training_title')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->keys()
                    ->first();

                return [
                    'organization' => $organization,
                    'region' => $rows->pluck('user.region')->filter()->unique()->sort()->implode(', ') ?: '—',
                    'submissions' => $rows->count(),
                    'top_training' => $topTraining,
                ];
            })
            ->sortByDesc('submissions')
            ->values();

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage('org_page');

        return (new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'org_page']
        ))->fragment('needs-assessment-by-organization');
    }

    /**
     * Graduates by LGU, grouped by OCD region — when not scoped to one
     * region, LGUs from different regions can share a name, so a single flat
     * ranking would blur them together. Moved here from the Tools tab.
     *
     * Regions with completed trainings but none tagged with an LGU still get
     * an entry (empty `lgus`) rather than being omitted — a region built
     * entirely from ATAR CSV imports (which deliberately leave `lgu` blank
     * instead of guessing it from the training title) would otherwise vanish
     * from this chart as if it had no completed trainings at all.
     *
     * Uses the completed trainings index() already loaded, reading each
     * training's participants_count rather than loading its whole roster.
     *
     * @param  Collection<int, TrainingRequest>  $completed  loaded withCount('participants')
     * @return array<string, array{total: int, lgus: array}>
     */
    private function graduatesByLgu(Collection $completed): array
    {
        $byRegion = $completed
            ->filter(fn (TrainingRequest $r) => filled($r->lgu))
            ->groupBy(fn (TrainingRequest $r) => $r->region ?: __('Unspecified Region'))
            ->map(function ($records) {
                $lgus = $records->groupBy('lgu')
                    ->map(fn ($lguRecords, $lgu) => [
                        'lgu' => $lgu,
                        'total' => $lguRecords->sum(fn (TrainingRequest $r) => max($r->participants_count, 1)),
                    ])
                    ->sortByDesc('total')
                    ->values()
                    ->all();

                return [
                    'total' => array_sum(array_column($lgus, 'total')),
                    'lgus' => $lgus,
                ];
            });

        $regionsWithoutLgu = $completed->pluck('region')->filter()->unique()->diff($byRegion->keys());

        foreach ($regionsWithoutLgu as $regionName) {
            $byRegion[$regionName] = ['total' => 0, 'lgus' => []];
        }

        return $byRegion->sortByDesc('total')->all();
    }

    /**
     * Registered participant counts per city, scoped to a single region when
     * one is given. Blank cities are excluded rather than grouped under
     * "Unspecified" — most existing participants registered before the city
     * field existed, and lumping them in would dwarf every real city.
     */
    private function participantsByCityChart(?string $region = null): array
    {
        return User::query()
            ->where('role', User::ROLE_PARTICIPANT)
            ->when($region, fn ($q) => $q->where('region', $region))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, count(*) as total')
            ->groupBy('city')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['city' => $row->city, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * Training counts per delivery mode from imported ATAR records — blank
     * modes (some rows in the source Training Database leave this unset) are
     * grouped under "Unspecified" rather than dropped.
     */
    private function atarTrainingsByModeChart(?string $region = null): array
    {
        // Built from get()+map() rather than pluck('total', 'mode_of_implementation') —
        // mode_of_implementation is nullable, and using a null value as an
        // array key triggers a deprecation notice.
        return AtarRecord::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw('mode_of_implementation, count(*) as total')
            ->groupBy('mode_of_implementation')
            ->get()
            ->map(fn ($row) => ['label' => $row->mode_of_implementation ?: __('Unspecified'), 'value' => (int) $row->total])
            ->values()
            ->all();
    }

    /**
     * Training counts per month from imported ATAR records, ordered
     * chronologically (Jan → Dec) rather than alphabetically or by import
     * order — months not present in the data are simply omitted.
     */
    private function atarTrainingsByMonthChart(?string $region = null): array
    {
        $counts = AtarRecord::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->whereNotNull('month')
            ->selectRaw('month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        return $counts
            ->map(fn ($total, $month) => ['label' => $month, 'value' => (int) $total, 'sort' => $this->monthSortKey($month)])
            ->sortBy('sort')
            ->map(fn (array $row) => ['label' => $row['label'], 'value' => $row['value']])
            ->values()
            ->all();
    }

    /**
     * Best-effort month index (1-12) for sorting free-text month labels like
     * "March" or "Apr" — unparseable labels sort last rather than breaking
     * the chart.
     */
    private function monthSortKey(string $month): int
    {
        try {
            return Carbon::parse("1 {$month} 2000")->month;
        } catch (\Throwable) {
            return 99;
        }
    }

    /**
     * Graduate totals per sector from imported ATAR records, sectors with
     * zero graduates omitted so the chart doesn't pad out with empty bars.
     */
    private function atarGraduatesBySectorChart(?string $region = null): array
    {
        $sectors = [
            'graduates_rdrrmc' => 'RDRRMC',
            'graduates_lgu' => 'LGU',
            'graduates_ldrrmo' => 'LDRRMO',
            'graduates_academe' => 'Academe',
            'graduates_cso' => 'CSO',
            'graduates_ngo' => 'NGO',
            'graduates_volunteer' => 'Volunteer',
            'graduates_private_sector' => 'Private Sector',
            'graduates_others' => 'Others',
        ];

        // Summed in SQL instead of loading every ATAR row (with its long
        // narrative text columns) just to add up nine numbers.
        $totals = AtarRecord::query()
            ->when($region, fn ($q) => $q->where('region', $region))
            ->selectRaw(collect(array_keys($sectors))->map(fn (string $column) => "COALESCE(SUM({$column}), 0) AS {$column}")->implode(', '))
            ->toBase()
            ->first();

        return collect($sectors)
            ->map(fn (string $label, string $column) => ['sector' => $label, 'graduates' => (int) $totals->{$column}])
            ->filter(fn (array $row) => $row['graduates'] > 0)
            ->sortByDesc('graduates')
            ->values()
            ->all();
    }
}
