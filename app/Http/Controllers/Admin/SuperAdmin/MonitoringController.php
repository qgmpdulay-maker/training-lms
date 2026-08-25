<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function regional(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        return view('admin.super-admin.monitoring.regional', [
            'filters' => $filters,
            'regions' => config('regions.list'),
            'categoryLabels' => TrainingRequest::$categoryLabels,
            'periodLabel' => $this->periodLabel($filters),
            'summary' => $this->summary($this->completedTrainings($filters)->get()),
            'regionalData' => $this->regionalData($filters),
            'threeYearData' => $this->threeYearData($filters),
            'chartData' => $this->chartData($filters),
        ]);
    }

    public function map(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $trainings = $this->completedTrainings($filters)->get();

        return view('admin.super-admin.monitoring.map', [
            'filters' => $filters,
            'regions' => config('regions.list'),
            'categoryLabels' => TrainingRequest::$categoryLabels,
            'summary' => $this->summary($trainings),
            'mapPoints' => $this->mapPoints($trainings),
            'regionBreakdown' => $this->regionBreakdown($trainings),
        ]);
    }

    private function completedTrainings(array $filters): Builder
    {
        return TrainingRequest::completed()->filteredBy($filters);
    }

    /**
     * @return array{regions: array, category: ?string, from: string, until: string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'regions' => array_filter((array) $request->query('regions', [])),
            'category' => $request->query('category') ?: null,
            'from' => $request->query('from') ?: now()->subYears(2)->startOfYear()->toDateString(),
            'until' => $request->query('until') ?: now()->endOfYear()->toDateString(),
        ];
    }

    private function periodLabel(array $filters): string
    {
        $from = $filters['from'] ? Carbon::parse($filters['from'])->format('d M Y') : 'the earliest record';
        $until = $filters['until'] ? Carbon::parse($filters['until'])->format('d M Y') : 'today';

        return "{$from} to {$until}";
    }

    /**
     * @param  Collection<int, TrainingRequest>  $trainings
     */
    private function summary(Collection $trainings): array
    {
        $participants = $trainings->sum('number_of_participants');
        $graduates = $trainings->sum(fn (TrainingRequest $t) => $t->graduates);

        return [
            'trainings' => $trainings->count(),
            'apb' => $trainings->where('category', TrainingRequest::CATEGORY_APB)->count(),
            'ta' => $trainings->where('category', TrainingRequest::CATEGORY_TA)->count(),
            'participants' => $participants,
            'graduates' => $graduates,
            'non_completers' => max($participants - $graduates, 0),
            'teams' => $trainings->sum('teams_organized'),
            'completion_rate' => $participants > 0 ? round($graduates / $participants * 100, 1).'%' : '—',
            'lgus' => $trainings->where('agency_type', TrainingRequest::AGENCY_TYPE_LGU)->pluck('requesting_agency')->filter()->unique()->count(),
            'ngas' => $trainings->where('agency_type', TrainingRequest::AGENCY_TYPE_NGA)->pluck('requesting_agency')->filter()->unique()->count(),
        ];
    }

    private function regionalData(array $filters): array
    {
        $trainings = $this->completedTrainings($filters)->get();

        $rows = collect(config('regions.list'))
            ->map(function (string $region) use ($trainings) {
                $regionTrainings = $trainings->where('region', $region);

                return array_merge(['label' => $region, 'short_label' => $region], $this->summary($regionTrainings));
            })
            ->filter(fn (array $row) => $row['trainings'] > 0)
            ->sortByDesc('graduates')
            ->values();

        $total = array_merge(['label' => 'Central (All OCDROs)', 'short_label' => 'Total'], $this->summary($trainings));

        return $rows->push($total)->all();
    }

    private function threeYearData(array $filters): array
    {
        $endYear = $filters['until'] ? Carbon::parse($filters['until'])->year : now()->year;

        $years = [];

        for ($year = $endYear - 2; $year <= $endYear; $year++) {
            $trainings = $this->completedTrainings(['regions' => $filters['regions'], 'category' => $filters['category']])
                ->whereYear('preferred_date', $year)
                ->get();

            $years[(string) $year] = $this->summary($trainings);
        }

        return $years;
    }

    private function chartData(array $filters): array
    {
        $trainings = $this->completedTrainings($filters)->get();

        return [
            'trainingsConducted' => $this->trainingsConductedChart($trainings, $filters),
            'graduatesPerRegion' => $this->graduatesPerRegionChart($trainings),
            'graduatesBySex' => [
                'male' => $trainings->sum('graduates_male'),
                'female' => $trainings->sum('graduates_female'),
            ],
            'graduatesByAgeRange' => [
                'age_18_30' => $trainings->sum('graduates_age_18_30'),
                'age_31_45' => $trainings->sum('graduates_age_31_45'),
                'age_46_59' => $trainings->sum('graduates_age_46_59'),
                'age_60_up' => $trainings->sum('graduates_age_60_up'),
            ],
        ];
    }

    private function trainingsConductedChart(Collection $trainings, array $filters): array
    {
        $from = Carbon::parse($filters['from']);
        $until = Carbon::parse($filters['until']);
        $byYear = $from->diffInMonths($until) > 36;

        $buckets = $trainings
            ->groupBy(fn (TrainingRequest $t) => $byYear ? $t->preferred_date->format('Y') : $t->preferred_date->format('Y-m'))
            ->sortKeys();

        return $buckets->map(fn ($rows, $bucket) => [
            'label' => $byYear ? $bucket : Carbon::createFromFormat('Y-m', $bucket)->format('M Y'),
            'apb' => $rows->where('category', TrainingRequest::CATEGORY_APB)->count(),
            'ta' => $rows->where('category', TrainingRequest::CATEGORY_TA)->count(),
        ])->values()->all();
    }

    private function graduatesPerRegionChart(Collection $trainings): array
    {
        $regions = $trainings->pluck('region')->filter()->unique()->sort()->values();

        if ($regions->isEmpty()) {
            $regions = collect(config('regions.list'));
        }

        return $regions->map(function (string $region) use ($trainings) {
            $regionTrainings = $trainings->where('region', $region);

            return [
                'region' => $region,
                'graduates' => $regionTrainings->sum(fn (TrainingRequest $t) => $t->graduates),
                'teams' => $regionTrainings->sum('teams_organized'),
            ];
        })->all();
    }

    /**
     * @param  Collection<int, TrainingRequest>  $trainings
     */
    private function mapPoints(Collection $trainings): array
    {
        return $trainings
            ->groupBy(fn (TrainingRequest $t) => $t->agency_type && $t->requesting_agency
                ? "{$t->agency_type}|{$t->requesting_agency}"
                : "region|{$t->region}")
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'name' => $first->agency_type ? $first->requesting_agency : ($first->region ?? 'Unassigned Region'),
                    'agency_type' => $first->agency_type ? strtoupper($first->agency_type) : null,
                    'region' => $first->region,
                    'latitude' => $first->map_coordinates[0],
                    'longitude' => $first->map_coordinates[1],
                    'trainings' => $rows->count(),
                    'graduates' => $rows->sum(fn (TrainingRequest $t) => $t->graduates),
                    'teams' => $rows->sum('teams_organized'),
                    'apb' => $rows->where('category', TrainingRequest::CATEGORY_APB)->count(),
                    'ta' => $rows->where('category', TrainingRequest::CATEGORY_TA)->count(),
                ];
            })
            ->sortByDesc('graduates')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TrainingRequest>  $trainings
     */
    private function regionBreakdown(Collection $trainings): array
    {
        return collect(config('regions.list'))
            ->map(fn (string $region) => [
                'region' => $region,
                'trainings' => $trainings->where('region', $region)->count(),
                'graduates' => $trainings->where('region', $region)->sum(fn (TrainingRequest $t) => $t->graduates),
                'teams' => $trainings->where('region', $region)->sum('teams_organized'),
            ])
            ->filter(fn (array $row) => $row['trainings'] > 0)
            ->sortByDesc('graduates')
            ->values()
            ->all();
    }
}
