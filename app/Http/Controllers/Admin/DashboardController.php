<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\SuperAdmin\MonitoringController;
use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingNeedsAssessment;
use App\Models\TrainingRequest;
use App\Support\CityDirectory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

        // One filter set for the whole dashboard: the From / Until / Region /
        // Training fields in the Regional Performance card at the top. Every
        // section below reads these same filters rather than having its own
        // region or year picker. A Regional Admin is always held to their own
        // region — the query string can't widen it.
        $requestedRegion = $request->query('region');
        $requestedTraining = $request->query('training_title');
        $filters = [
            'regions' => $user->isSuperAdmin()
                ? (is_string($requestedRegion) && in_array($requestedRegion, config('regions.list'), true) ? [$requestedRegion] : [])
                : [$user->region],
            'training_title' => is_string($requestedTraining) && $requestedTraining !== '' ? $requestedTraining : null,
            'from' => $this->queryDate($request, 'from'),
            'until' => $this->queryDate($request, 'until'),
        ];
        $region = $filters['regions'][0] ?? null;

        // Every completed training matching the filters, loaded once and
        // shared by the stat cards, the map, and every chart built from
        // completed trainings.
        $completed = MonitoringController::completedTrainings($filters)->get();

        $graduatesByTraining = $this->graduatesByTrainingChart($completed);
        $requestsByTraining = $this->requestsByTrainingChart($filters);
        $statusBreakdown = $this->statusBreakdownChart($filters);
        $graduatesBySex = [
            'male' => $completed->sum('graduates_male'),
            'female' => $completed->sum('graduates_female'),
        ];
        $graduatesByAgeRange = $this->graduatesByAgeRangeChart($completed);
        $mostNeededTrainings = $this->mostNeededTrainingsChart($filters);

        $cityTotals = MonitoringController::cityTotals($completed);

        [$scheduledStart, $scheduledEnd] = $this->scheduledRange($filters);

        $chartData = [
            'requestsByTraining' => $requestsByTraining,
            'statusBreakdown' => $statusBreakdown,
            'graduatesBySex' => $graduatesBySex,
            'graduatesByAgeRange' => $graduatesByAgeRange,
            'mostNeededTrainings' => $mostNeededTrainings,
            'requestedVsAccomplished' => $this->requestedVsAccomplishedChart($filters, $scheduledStart, $scheduledEnd),
            'categoryComparison' => $this->categoryComparisonChart($completed),
            'threeYearTrend' => $this->threeYearTrendChart($filters),
        ];

        // Super Admin and Regional Admins get the same dashboard; a Regional
        // Admin's is just locked to their region (see $filters above).
        $data = [
            'user' => $user,
            'regionLocked' => ! $user->isSuperAdmin(),
            'filters' => $filters,
            'region' => $region,
            'regions' => config('regions.list'),
            'trainingTitles' => collect(config('trainings.catalog'))->pluck('title'),
            'monitoringSummary' => MonitoringController::summary($completed),
            'cityTotals' => $cityTotals,
            'graduatesByCity' => $this->graduatesByCity($cityTotals, $region),
            'stats' => [
                // Instructors aren't tied to a training or a date, so only the
                // region narrows this one.
                'instructors' => Instructor::query()->when($region, fn ($q) => $q->where('region', $region))->count(),
                'tna_submissions' => $this->needsAssessments($filters)->count(),
                'upcoming_trainings' => $this->upcomingTrainingsCount($filters),
            ],
            'chartData' => $chartData,
            'scheduledPeriod' => $scheduledStart->format('M Y').' – '.$scheduledEnd->format('M Y'),
            'insights' => $this->buildInsights(
                statusBreakdown: $statusBreakdown,
                graduatesByTraining: $graduatesByTraining,
                requestsByTraining: $requestsByTraining,
                graduatesByAgeRange: $graduatesByAgeRange,
                // A region-by-region comparison only means something when no
                // single region is picked — so never for a Regional Admin.
                graduatesByRegion: $region ? null : $this->graduatesByRegionChart($completed),
                graduatesBySex: $graduatesBySex,
                mostNeededTrainings: $mostNeededTrainings,
                lowestCompletionRateTraining: $this->lowestCompletionRateTraining($filters),
                lguNgaReach: $this->lguNgaReach($completed),
            ),
            'needsAssessmentByOrganization' => $this->needsAssessmentByOrganization($request, $filters),
        ];

        // Changing a filter submits via fetch() instead of a plain GET
        // navigation, so the page doesn't reload — see submitDashboardFilter()
        // in admin/dashboard/index.blade.php. Everything the filters touch
        // lives in one partial, returned here re-rendered along with the data
        // its charts are drawn from (the map carries its own data in the
        // markup).
        if ($request->ajax()) {
            return response()->json([
                'body_html' => view('admin.dashboard.body', $data)->render(),
                'chartData' => $chartData,
            ]);
        }

        return view('admin.dashboard.index', $data);
    }

    /**
     * A From/Until value from the query string as Y-m-d, or null when it's
     * missing or isn't a real date — so a hand-edited URL can't feed garbage
     * into the date math further down.
     */
    private function queryDate(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Training Needs Assessment submissions matching the dashboard filters:
     * region by the submitting participant's region, dates by when it was
     * submitted, and training by the training it recommended.
     *
     * @return Builder<TrainingNeedsAssessment>
     */
    private function needsAssessments(array $filters): Builder
    {
        return TrainingNeedsAssessment::query()
            ->when($filters['regions'], fn ($q) => $q->whereHas('user', fn ($user) => $user->whereIn('region', $filters['regions'])))
            ->when($filters['training_title'], fn ($q) => $q->where('recommended_training_title', $filters['training_title']))
            ->when($filters['from'], fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['until'], fn ($q) => $q->whereDate('created_at', '<=', $filters['until']));
    }

    /**
     * Approved or under-review trainings still ahead — today onward, or from
     * the filter's From date if that's later — within the rest of the filters.
     */
    private function upcomingTrainingsCount(array $filters): int
    {
        $today = today()->toDateString();

        return TrainingRequest::filteredBy(['from' => max($filters['from'] ?? $today, $today)] + $filters)
            ->whereIn('status', [TrainingRequest::STATUS_APPROVED, TrainingRequest::STATUS_UNDER_REVIEW])
            ->count();
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

            // Only meaningful nationwide (this whole callout is skipped
            // whenever a single region is showing, including every Regional
            // Admin's dashboard, since $graduatesByRegion is null then) — a
            // coverage gap, not just a
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
    private function lowestCompletionRateTraining(array $filters): ?array
    {
        $rows = TrainingRequest::filteredBy($filters)
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
     * LGUs/NGAs Covered stat cards, from the same filtered trainings.
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
     * Graduate totals per training course, completed trainings only, highest
     * first. No longer drawn as a chart — it feeds the "has produced the most
     * graduates" insight.
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
     * Training request counts per training course — every request matching
     * the dashboard filters, regardless of status.
     */
    private function requestsByTrainingChart(array $filters): array
    {
        return TrainingRequest::filteredBy($filters)
            ->selectRaw('training_title, count(*) as total')
            ->groupBy('training_title')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['training' => $row->training_title, 'requests' => (int) $row->total])
            ->all();
    }

    /**
     * Request counts by status — the pipeline from newly submitted through
     * completed — for every request matching the dashboard filters.
     */
    private function statusBreakdownChart(array $filters): array
    {
        $counts = TrainingRequest::filteredBy($filters)
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
     * The months the Scheduled vs Completed chart covers: the dashboard's
     * From/Until when set, otherwise the current calendar year. With only one
     * end set, the other falls back to the start of Until's year, or the end
     * of the current year after From.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function scheduledRange(array $filters): array
    {
        $start = match (true) {
            $filters['from'] !== null => Carbon::parse($filters['from'])->startOfMonth(),
            $filters['until'] !== null => Carbon::parse($filters['until'])->startOfYear(),
            default => now()->startOfYear(),
        };
        $end = $filters['until'] !== null
            ? Carbon::parse($filters['until'])->endOfMonth()
            : now()->endOfYear();

        if ($end->lt($start)) {
            $end = $start->copy()->endOfYear();
        }

        return [$start, $end];
    }

    /**
     * Cumulative Scheduled vs Completed for TA trainings, month by month
     * across scheduledRange().
     *
     * Deliberately not a target-vs-actual: Technical Assistance has no targets
     * set for it, so the honest comparison is how many were put on the calendar
     * against how many were actually delivered, accumulating month by month.
     * Both series run on preferred_date, so a training counts in the month it
     * was scheduled for rather than the month the record was created.
     *
     * The array keys stay requested/accomplished, but the chart is labelled
     * "Scheduled vs Completed" — only Super Admin can create a TrainingRequest
     * today, so the first series is this office's own schedule, not incoming
     * demand. If agency-as-requester lands and SOURCE_PUBLIC_PORTAL rows start
     * appearing, the "Requested" framing becomes true again and the label
     * should go back.
     *
     * @return array<int, array{month: string, requested: int, accomplished: int}>
     */
    private function requestedVsAccomplishedChart(array $filters, Carbon $start, Carbon $end): array
    {
        $counts = TrainingRequest::query()
            ->where('category', TrainingRequest::CATEGORY_TA)
            ->filteredBy([
                'from' => $filters['from'] ?? $start->toDateString(),
                'until' => $filters['until'] ?? $end->toDateString(),
            ] + $filters)
            ->selectRaw('YEAR(preferred_date) as year_number, MONTH(preferred_date) as month_number, count(*) as requested, sum(case when status = ? then 1 else 0 end) as accomplished', [TrainingRequest::STATUS_COMPLETED])
            ->groupBy('year_number', 'month_number')
            ->get()
            ->keyBy(fn ($row) => $row->year_number.'-'.$row->month_number);

        // Month names alone are ambiguous once the range crosses a year.
        $labelFormat = $start->year === $end->year ? 'M' : 'M Y';
        $runningRequested = 0;
        $runningAccomplished = 0;
        $months = [];

        for ($month = $start->copy()->startOfMonth(); $month->lte($end); $month->addMonth()) {
            $row = $counts->get($month->year.'-'.$month->month);
            $runningRequested += (int) ($row->requested ?? 0);
            $runningAccomplished += (int) ($row->accomplished ?? 0);

            $months[] = [
                'month' => $month->format($labelFormat),
                'requested' => $runningRequested,
                'accomplished' => $runningAccomplished,
            ];
        }

        return $months;
    }

    /**
     * APB against TA on the two measures that matter: how many trainings were
     * run, and how many people graduated from them.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     * @return array<int, array{label: string, trainings: int, graduates: int}>
     */
    private function categoryComparisonChart(Collection $completed): array
    {
        return collect(TrainingRequest::$categoryLabels)
            ->map(function (string $label, string $category) use ($completed) {
                $rows = $completed->where('category', $category);

                return [
                    'label' => $label,
                    'trainings' => $rows->count(),
                    'graduates' => $rows->sum(fn (TrainingRequest $t) => $t->graduates),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Graduates per training over three years, so a course that is quietly
     * tailing off is easy to spot. The three years end at the Until date's
     * year when one is set, otherwise this year; region and training follow
     * the dashboard filters.
     *
     * Returns every training rather than a top slice: the chart shows one at a
     * time, picked from a dropdown, so all of them need to be available to the
     * browser. Ordered by three-year total, which makes the busiest training
     * the sensible default selection.
     *
     * @return array{years: array<int, int>, trainings: array<int, array{training: string, graduates: array<int, int>}>}
     */
    private function threeYearTrendChart(array $filters): array
    {
        $endYear = $filters['until'] !== null ? Carbon::parse($filters['until'])->year : now()->year;
        $years = collect(range($endYear - 2, $endYear));

        $rows = TrainingRequest::completed()
            ->filteredBy(['regions' => $filters['regions'], 'training_title' => $filters['training_title']])
            ->whereYear('preferred_date', '>=', $years->first())
            ->whereYear('preferred_date', '<=', $years->last())
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

    /**
     * Graduate totals per age bracket, completed trainings only.
     *
     * @param  Collection<int, TrainingRequest>  $completed
     */
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
     * first, among the submissions matching the dashboard filters.
     */
    private function mostNeededTrainingsChart(array $filters): array
    {
        return $this->needsAssessments($filters)
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
    private function needsAssessmentByOrganization(Request $request, array $filters): LengthAwarePaginator
    {
        // Skips the large answers/category_scores JSON columns — only the
        // recommendation and the submitter's organization/region are used.
        $submissions = $this->needsAssessments($filters)->select(['id', 'user_id', 'recommended_training_title'])
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
     * The Graduates by Location map's figures as a list for the Graduates by
     * City card: cities grouped by the region they're in, busiest first. A
     * city is where the graduate lives (see MonitoringController::cityTotals()),
     * so a region's tab can hold graduates of another region's trainings —
     * which is why a Regional Admin's own region is always the first tab.
     *
     * @return array<string, array{total: int, cities: array<int, array{name: string, province: string, total: int}>}>
     */
    private function graduatesByCity(array $cityTotals, ?string $ownRegion): array
    {
        return collect($cityTotals['cities'])
            ->groupBy(fn (array $city) => CityDirectory::find($city['id'])['region'])
            ->map(fn (Collection $cities) => [
                'total' => $cities->sum('graduates'),
                'cities' => $cities
                    ->sortByDesc('graduates')
                    ->map(fn (array $city) => ['name' => $city['name'], 'province' => $city['province'], 'total' => $city['graduates']])
                    ->values()
                    ->all(),
            ])
            ->sortBy(fn (array $group, string $region) => [$region === $ownRegion ? 0 : 1, -$group['total']])
            ->all();
    }
}
