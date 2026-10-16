<?php

namespace Database\Seeders;

use App\Support\CityDirectory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gives the nationwide demo participants a City, so the dashboards' Graduates
 * by Location map has something to show. Only fills demo accounts that don't
 * have one yet, so it's safe to run again, and run on its own against a
 * database NationwideDemoSeeder already filled:
 *
 *     php artisan db:seed --class=DemoGraduateCitiesSeeder
 *
 * Each region's participants are spread over a dozen or so of its cities and
 * municipalities with a few taking most of them, the way a region's trainees
 * cluster around its bigger centres — not one person per town. OCD Central
 * has no territory of its own, so its participants are placed in Metro Manila.
 */
class DemoGraduateCitiesSeeder extends Seeder
{
    private const DEMO_EMAIL_DOMAIN = '@nationwide.demo.ocd.local';

    private const CITIES_PER_REGION = 14;

    public function run(): void
    {
        $citiesByRegion = collect(CityDirectory::all())->groupBy('region');

        $people = DB::table('users')
            ->where('role', 'participant')
            ->where('email', 'like', '%'.self::DEMO_EMAIL_DOMAIN)
            ->where(fn ($q) => $q->whereNull('city')->orWhere('city', ''))
            ->get(['id', 'region']);

        foreach ($people->groupBy('region') as $region => $group) {
            $pool = $citiesByRegion[$region === 'OCD Central' ? 'NCR' : $region] ?? null;

            if (! $pool) {
                continue;
            }

            // Ordered by a hash rather than shuffled, and the weighted picks
            // below seeded by region, so a re-seed draws the same map.
            $hubs = $pool->sortBy(fn (array $city) => crc32($region.$city['name']))->take(self::CITIES_PER_REGION)->values();
            mt_srand(crc32($region));
            // Zipf-like weights: the first hub gets the most people, tailing off.
            $weights = $hubs->keys()->map(fn (int $rank) => 1 / ($rank + 1))->all();

            foreach ($group as $person) {
                DB::table('users')->where('id', $person->id)->update([
                    'city' => CityDirectory::label($hubs[$this->weightedIndex($weights)]),
                ]);
            }
        }

        mt_srand();

        $this->command?->info("Gave {$people->count()} demo participants a city.");
    }

    private function weightedIndex(array $weights): int
    {
        $pick = mt_rand() / mt_getrandmax() * array_sum($weights);

        foreach ($weights as $index => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $index;
            }
        }

        return array_key_last($weights);
    }
}
