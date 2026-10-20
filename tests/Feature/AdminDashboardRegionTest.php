<?php

namespace Tests\Feature;

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Pdo\Sqlite;
use Tests\TestCase;

/**
 * Super Admin and Regional Admins share one dashboard; a Regional Admin's is
 * locked to their own region, and the `region` query string can't widen it.
 */
class AdminDashboardRegionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The dashboard's charts use MySQL's YEAR() and MONTH(), which the
        // in-memory SQLite test database doesn't have.
        $pdo = DB::connection()->getPdo();
        $functions = [
            'YEAR' => fn (?string $date) => $date === null ? null : (int) substr($date, 0, 4),
            'MONTH' => fn (?string $date) => $date === null ? null : (int) substr($date, 5, 2),
        ];
        foreach ($functions as $name => $function) {
            $pdo instanceof Sqlite
                ? $pdo->createFunction($name, $function, 1)
                : $pdo->sqliteCreateFunction($name, $function, 1);
        }
    }

    /**
     * A participant who has completed a training in their region, living in
     * the given city.
     */
    private function graduate(string $region, string $city): User
    {
        $participant = User::factory()->create(['region' => $region, 'city' => $city]);

        $training = new TrainingRequest([
            'training_slug' => 'community-based-drrm',
            'training_title' => 'Community-Based DRRM',
            'region' => $region,
            'preferred_date' => now()->subMonth()->toDateString(),
            'venue' => 'Municipal Hall',
            'number_of_participants' => 1,
            'status' => TrainingRequest::STATUS_COMPLETED,
        ]);
        $training->save();
        $training->participants()->sync([$participant->id]);
        $training->syncGraduateCountsFromParticipants();

        return $participant->refresh();
    }

    public function test_a_regional_admin_stays_on_their_own_region_whatever_the_query_string_asks_for(): void
    {
        $own = $this->graduate('Region III', 'Angeles, Pampanga');
        $own->deployments()->create([
            'deployment' => 'Mayon Operations',
            'deployment_date' => now()->subWeek()->toDateString(),
            'deployment_role' => 'Team Leader',
        ]);
        $this->graduate('Region IV-A', 'Sta. Rosa, Laguna');

        $cityNames = fn (array $cityTotals) => collect($cityTotals['cities'])->pluck('name')->sort()->values()->all();

        // Asking for another region doesn't widen it, and the map outlines
        // the admin's own region.
        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.dashboard', ['region' => 'Region IV-A']))
            ->assertOk()
            ->assertViewHas('regionLocked', true)
            ->assertViewHas('filters', fn (array $filters) => $filters['regions'] === ['Region III'])
            ->assertViewHas('monitoringSummary', fn (array $summary) => $summary['graduates'] === 1 && $summary['deployed'] === 1)
            ->assertViewHas('cityTotals', fn (array $totals) => $cityNames($totals) === ['City of Angeles'])
            ->assertViewHas('graduatesByCity', fn (array $byCity) => array_keys($byCity) === ['Region III'])
            ->assertSee('data-only-region="Region III"', false);

        // Super Admin sees every region, with none outlined...
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('regionLocked', false)
            ->assertViewHas('cityTotals', fn (array $totals) => $cityNames($totals) === ['City of Angeles', 'City of Santa Rosa'])
            ->assertSee('data-only-region=""', false);

        // ...and can narrow it to one region, which is then outlined.
        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard', ['region' => 'Region IV-A']))
            ->assertOk()
            ->assertViewHas('cityTotals', fn (array $totals) => $cityNames($totals) === ['City of Santa Rosa'])
            ->assertSee('data-only-region="Region IV-A"', false);
    }
}
