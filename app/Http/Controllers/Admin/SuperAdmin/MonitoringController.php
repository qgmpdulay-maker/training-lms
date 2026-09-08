<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Reachable by both Regional Admins and Super Admin (see routes/web.php) —
     * a Regional Admin's region filter is force-overridden below rather than
     * merged, so it can't be widened by editing the `regions[]` query string.
     * Super Admin's own dashboard embeds this same map inline (see
     * DashboardController) — this standalone page still exists for Regional
     * Admins' own dashboard link and the Tools page's "Graduates Map" link.
     */
    public function map(Request $request): View
    {
        $user = $request->user();
        $filters = $this->filtersFromRequest($request);

        if ($user->isAdmin()) {
            $filters['regions'] = [$user->region];
        }

        $trainings = static::completedTrainings($filters)->get();

        return view('admin.super-admin.monitoring.map', [
            'filters' => $filters,
            'regionLocked' => $user->isAdmin(),
            'regions' => config('regions.list'),
            'trainingTitles' => collect(config('trainings.catalog'))->pluck('title'),
            'summary' => static::summary($trainings),
            'mapPoints' => static::mapPoints($trainings),
        ]);
    }

    public static function completedTrainings(array $filters): Builder
    {
        return TrainingRequest::completed()->filteredBy($filters);
    }

    /**
     * @return array{regions: array, training_title: ?string, from: ?string, until: ?string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'regions' => array_filter((array) $request->query('regions', [])),
            'training_title' => $request->query('training_title') ?: null,
            'from' => $request->query('from') ?: null,
            'until' => $request->query('until') ?: null,
        ];
    }

    /**
     * @param  Collection<int, TrainingRequest>  $trainings
     */
    public static function summary(Collection $trainings): array
    {
        $participants = $trainings->sum('number_of_participants');
        $graduates = $trainings->sum(fn (TrainingRequest $t) => $t->graduates);

        return [
            'trainings' => $trainings->count(),
            'participants' => $participants,
            'graduates' => $graduates,
            'non_completers' => max($participants - $graduates, 0),
            'teams' => $trainings->sum('teams_organized'),
            'completion_rate' => $participants > 0 ? round($graduates / $participants * 100, 1).'%' : '—',
            'lgus' => $trainings->where('agency_type', TrainingRequest::AGENCY_TYPE_LGU)->pluck('requesting_agency')->filter()->unique()->count(),
            'ngas' => $trainings->where('agency_type', TrainingRequest::AGENCY_TYPE_NGA)->pluck('requesting_agency')->filter()->unique()->count(),
        ];
    }

    /**
     * Per-region rollup plus a "Central (All OCDROs)" total row — feeds the
     * Regional Performance table on the Super Admin dashboard.
     */
    public static function regionalData(array $filters): array
    {
        $trainings = static::completedTrainings($filters)->get();

        $rows = collect(config('regions.list'))
            ->map(function (string $region) use ($trainings) {
                $regionTrainings = $trainings->where('region', $region);

                return array_merge(['label' => $region, 'short_label' => $region], static::summary($regionTrainings));
            })
            ->filter(fn (array $row) => $row['trainings'] > 0)
            ->sortByDesc('graduates')
            ->values();

        $total = array_merge(['label' => 'Central (All OCDROs)', 'short_label' => 'Total'], static::summary($trainings));

        return $rows->push($total)->all();
    }

    /**
     * Quick top-line callouts from the regional breakdown — which region leads
     * on graduates, completion rate, and trainings conducted — so a Super Admin
     * gets the headline without reading the whole table. Only real regions are
     * considered, never the "Central (All OCDROs)" aggregate row.
     */
    public static function regionalHighlights(array $regionalData): array
    {
        $regions = collect($regionalData)->reject(fn (array $row) => $row['label'] === 'Central (All OCDROs)');

        if ($regions->isEmpty()) {
            return [];
        }

        $topGraduates = $regions->sortByDesc('graduates')->first();
        $topCompletion = $regions
            ->filter(fn (array $row) => $row['participants'] > 0)
            ->sortByDesc(fn (array $row) => (float) $row['completion_rate'])
            ->first();
        $mostTrainings = $regions->sortByDesc('trainings')->first();

        return array_values(array_filter([
            $topGraduates ? ['label' => 'Most Graduates', 'region' => $topGraduates['label'], 'value' => $topGraduates['graduates']] : null,
            $topCompletion ? ['label' => 'Best Completion Rate', 'region' => $topCompletion['label'], 'value' => $topCompletion['completion_rate']] : null,
            $mostTrainings ? ['label' => 'Most Trainings Conducted', 'region' => $mostTrainings['label'], 'value' => $mostTrainings['trainings']] : null,
        ]));
    }

    /**
     * Shared with both dashboards' embedded maps (see DashboardController),
     * hence public static rather than a private instance method like the
     * rest of this controller's helpers.
     *
     * @param  Collection<int, TrainingRequest>  $trainings
     */
    public static function mapPoints(Collection $trainings): array
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
                ];
            })
            ->sortByDesc('graduates')
            ->values()
            ->all();
    }
}
