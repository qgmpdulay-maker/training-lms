<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\SuperAdmin\MonitoringController;
use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\TrainingNeedsAssessment;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Defaults to the current year so the chart opens on the most relevant
        // slice of data rather than every year stacked together; "All Years"
        // is still one filter away for anyone who wants the full history. Only
        // this one chart is year-scoped — everything else on the dashboard
        // stays all-time.
        $year = $request->query('year', (string) now()->year);

        if (! $user->isSuperAdmin()) {
            $completed = TrainingRequest::completed()->where('region', $user->region)->get();

            $availableYears = $this->availableYears($user->region);
            $graduatesByTrainingCompleted = TrainingRequest::completed()
                ->where('region', $user->region)
                ->when($year !== 'all', fn ($q) => $q->whereYear('preferred_date', $year))
                ->get();
            $graduatesByTraining = $this->graduatesByTrainingChart($graduatesByTrainingCompleted);
            $requestsByTraining = $this->requestsByTrainingChart($user->region);
            $statusBreakdown = $this->statusBreakdownChart($user->region);
            $graduatesBySex = [
                'male' => $completed->sum('graduates_male'),
                'female' => $completed->sum('graduates_female'),
            ];
            $graduatesByAgeRange = $this->graduatesByAgeRangeChart($completed);
            $mostNeededTrainings = $this->mostNeededTrainingsChart($user->region);

            return view('admin.dashboard-regional', [
                'user' => $user,
                'year' => $year,
                'availableYears' => $availableYears,
                'region' => $user->region,
                'graduatesByLgu' => $this->graduatesByLgu($user->region),
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
                ),
            ]);
        }

        $completed = TrainingRequest::completed()->get();

        $availableYears = $this->availableYears();
        $graduatesByTrainingCompleted = TrainingRequest::completed()
            ->when($year !== 'all', fn ($q) => $q->whereYear('preferred_date', $year))
            ->get();
        $graduatesByTraining = $this->graduatesByTrainingChart($graduatesByTrainingCompleted);
        $requestsByTraining = $this->requestsByTrainingChart();
        $statusBreakdown = $this->statusBreakdownChart();
        $graduatesByRegion = $this->graduatesByRegionChart($completed);
        $graduatesBySex = [
            'male' => $completed->sum('graduates_male'),
            'female' => $completed->sum('graduates_female'),
        ];
        $graduatesByAgeRange = $this->graduatesByAgeRangeChart($completed);
        $mostNeededTrainings = $this->mostNeededTrainingsChart();

        // Regional Monitoring and the Graduates Map used to be their own tabs —
        // folded into the dashboard so Super Admin has one place to look,
        // filterable independently of the charts above (which stay all-time).
        $monitoringFilters = [
            'regions' => array_filter((array) $request->query('regions', [])),
            'training_title' => $request->query('training_title') ?: null,
            'from' => $request->query('from') ?: null,
            'until' => $request->query('until') ?: null,
        ];
        $monitoringTrainings = MonitoringController::completedTrainings($monitoringFilters)->get();
        $regionalData = MonitoringController::regionalData($monitoringFilters);

        // Needs Assessment used to show its own "What Training Is Needed" chart
        // and a per-organization breakdown table — the chart duplicated
        // mostNeededTrainingsChart() above, so only the breakdown table (which
        // has no dashboard equivalent) moved over.
        $needsAssessmentByOrganization = $this->needsAssessmentByOrganization($request);

        return view('admin.super-admin.dashboard', [
            'user' => $user,
            'year' => $year,
            'availableYears' => $availableYears,
            'stats' => [
                'instructors' => Instructor::count(),
                'tna_submissions' => TrainingNeedsAssessment::count(),
                'upcoming_trainings' => TrainingRequest::where('preferred_date', '>=', today())
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
            ],
            'insights' => $this->buildInsights(
                statusBreakdown: $statusBreakdown,
                graduatesByTraining: $graduatesByTraining,
                requestsByTraining: $requestsByTraining,
                graduatesByAgeRange: $graduatesByAgeRange,
                graduatesByRegion: $graduatesByRegion,
                graduatesBySex: $graduatesBySex,
                mostNeededTrainings: $mostNeededTrainings,
            ),
            'monitoringFilters' => $monitoringFilters,
            'regions' => config('regions.list'),
            'trainingTitles' => collect(config('trainings.catalog'))->pluck('title'),
            'monitoringSummary' => MonitoringController::summary($monitoringTrainings),
            'regionalData' => $regionalData,
            'regionalHighlights' => MonitoringController::regionalHighlights($regionalData),
            'mapPoints' => MonitoringController::mapPoints($monitoringTrainings),
            'needsAssessmentByOrganization' => $needsAssessmentByOrganization,
            'region' => null,
            'graduatesByLgu' => $this->graduatesByLgu(),
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

        return $insights;
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
        $submissions = TrainingNeedsAssessment::with('user')->get();

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
     * @return array<string, array{total: int, lgus: array}>
     */
    private function graduatesByLgu(?string $region = null): array
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
