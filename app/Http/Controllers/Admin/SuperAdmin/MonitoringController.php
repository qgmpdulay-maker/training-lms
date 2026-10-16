<?php

namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ParticipantDeployment;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Support\CityDirectory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * No routes of its own any more — the standalone Graduates Map page is gone.
 * What's left are the stat-card and city-map figures both admin dashboards
 * build from (see DashboardController).
 */
class MonitoringController extends Controller
{
    public static function completedTrainings(array $filters): Builder
    {
        return TrainingRequest::completed()->filteredBy($filters);
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
            'deployed' => self::deployedCount($trainings),
            'non_completers' => max($participants - $graduates, 0),
            'teams' => $trainings->sum('teams_organized'),
            'completion_rate' => $participants > 0 ? round($graduates / $participants * 100, 1).'%' : '—',
            'lgus' => $trainings->where('agency_type', TrainingRequest::AGENCY_TYPE_LGU)->pluck('requesting_agency')->filter()->unique()->count(),
            'ngas' => $trainings->where('agency_type', TrainingRequest::AGENCY_TYPE_NGA)->pluck('requesting_agency')->filter()->unique()->count(),
        ];
    }

    /**
     * How many distinct graduates of these trainings have since been deployed
     * to an operation (see Admin\ParticipantDeploymentController).
     *
     * Counted per person, not per deployment: someone sent out three times is
     * one deployed graduate, and someone who attended two of these trainings
     * is still only counted once.
     *
     * @param  Collection<int, TrainingRequest>  $trainings
     */
    private static function deployedCount(Collection $trainings): int
    {
        if ($trainings->isEmpty()) {
            return 0;
        }

        return User::whereHas('deployments')
            ->where(function ($query) use ($trainings) {
                $ids = $trainings->pluck('id');
                $query->whereHas('participatingTrainingRequests', fn ($q) => $q->whereIn('training_requests.id', $ids))
                    ->orWhereHas('trainingRequests', fn ($q) => $q->whereIn('training_requests.id', $ids));
            })
            ->count();
    }

    /**
     * Graduates, deployed people, and trainings per city, for the Graduates
     * by Location map on both dashboards. Trainings don't record where they were held,
     * so a graduate counts in the city they live in — their City on file,
     * which an admin can also set on the Deployments page.
     *
     * Graduates count the same way as the Graduates card: once per completed
     * training on a person's record. Deployed counts each person once.
     * Trainings counts how many of these trainings had a graduate from that
     * city. Anyone whose City is blank or can't be matched to a city or
     * municipality (see CityDirectory::match()) is left off the map and
     * counted in `unplaced` instead, as is every graduate of a training with
     * no participant roster (e.g. an ATAR import), since there's no one to
     * look a city up for.
     *
     * @param  Collection<int, TrainingRequest>  $trainings
     * @return array{cities: array<int, array{id: int, name: string, province: string, graduates: int, deployed: int, trainings: int}>, unplaced: int, unplaced_people: int}
     */
    public static function cityTotals(Collection $trainings): array
    {
        $roster = DB::table('training_request_user')
            ->whereIn('training_request_id', $trainings->pluck('id'))
            ->get(['training_request_id', 'user_id']);

        $withRoster = $roster->pluck('training_request_id')->flip();
        $unplaced = $trainings
            ->reject(fn (TrainingRequest $t) => $withRoster->has($t->id))
            ->sum(fn (TrainingRequest $t) => $t->graduates);

        $people = User::whereIn('id', $roster->pluck('user_id')->unique())->get(['id', 'city', 'region']);
        $deployed = ParticipantDeployment::whereIn('user_id', $people->pluck('id'))->distinct()->pluck('user_id')->flip();

        // Many people share a City value, so each value is matched once.
        $matches = [];
        $cityOf = $people->mapWithKeys(function (User $person) use (&$matches) {
            $key = $person->city.'|'.$person->region;
            if (! array_key_exists($key, $matches)) {
                $matches[$key] = CityDirectory::match($person->city, $person->region)['id'] ?? null;
            }

            return [$person->id => $matches[$key]];
        });

        $totals = [];
        $unplacedPeople = [];
        foreach ($roster as $row) {
            $cityId = $cityOf[$row->user_id] ?? null;

            if ($cityId === null) {
                $unplaced++;
                $unplacedPeople[$row->user_id] = true;

                continue;
            }

            $totals[$cityId]['graduates'] = ($totals[$cityId]['graduates'] ?? 0) + 1;
            $totals[$cityId]['trainings'][$row->training_request_id] = true;
            if ($deployed->has($row->user_id)) {
                $totals[$cityId]['deployed'][$row->user_id] = true;
            }
        }

        return [
            'cities' => collect($totals)
                ->map(function (array $total, int $cityId) {
                    $city = CityDirectory::find($cityId);

                    return [
                        'id' => $cityId,
                        'name' => $city['name'],
                        'province' => $city['province'],
                        'graduates' => $total['graduates'],
                        'deployed' => count($total['deployed'] ?? []),
                        'trainings' => count($total['trainings']),
                    ];
                })
                ->sortByDesc('graduates')
                ->values()
                ->all(),
            'unplaced' => $unplaced,
            'unplaced_people' => count($unplacedPeople),
        ];
    }
}
