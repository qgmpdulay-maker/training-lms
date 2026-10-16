<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SuperAdmin\MonitoringController;
use App\Models\ParticipantDeployment;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Support\CityDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which city a graduate is counted under on the Graduates by Location map:
 * matching their free-text City, setting it from the Deployments page, and
 * the per-city totals the map is drawn from.
 */
class GraduateCityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A completed training with these people on its roster.
     *
     * @param  array<int, User>  $people
     */
    private function training(array $people, string $region = 'Region III'): TrainingRequest
    {
        $training = new TrainingRequest([
            'training_slug' => 'community-based-drrm',
            'training_title' => 'Community-Based DRRM',
            'region' => $region,
            'preferred_date' => now()->subMonth()->toDateString(),
            'venue' => 'Municipal Hall',
            'number_of_participants' => count($people),
            'status' => TrainingRequest::STATUS_COMPLETED,
        ]);
        $training->save();
        $training->participants()->sync(collect($people)->pluck('id'));
        $training->syncGraduateCountsFromParticipants();

        return $training->refresh();
    }

    private function graduate(array $attributes = []): User
    {
        $person = User::factory()->create(['region' => 'Region III'] + $attributes);
        $this->training([$person]);

        return $person->refresh();
    }

    public function test_free_text_cities_match_the_way_people_write_them(): void
    {
        $label = fn (?string $value, ?string $region = null) => ($city = CityDirectory::match($value, $region)) ? CityDirectory::label($city) : null;

        $this->assertSame('Angeles City, Pampanga', $label('Angeles, Pampanga'));
        $this->assertSame('City of Biñan, Laguna', $label('Binan City'));
        $this->assertSame('Quezon City, Metro Manila', $label('Quezon City', 'Region III'));
        $this->assertSame('Quezon, Palawan', $label('Quezon, Palawan'));
        $this->assertSame('City of San Fernando, Pampanga', $label('San Fernando, Pampanga'));
        $this->assertSame('San Fernando, Cebu', $label('San Fernando', 'Region VII'));
        $this->assertSame('City of Santa Rosa, Laguna', $label('Sta. Rosa, Laguna'));

        // Seven San Fernandos and nothing to tell them apart.
        $this->assertNull($label('San Fernando'));
        $this->assertNull($label('Demo City'));
        $this->assertNull($label(''));
    }

    public function test_regional_admin_can_set_their_graduates_city_in_the_lists_spelling(): void
    {
        $graduate = $this->graduate();

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.deployments.city', $graduate), ['city' => 'malolos, bulacan'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('City of Malolos, Bulacan', $graduate->refresh()->city);
    }

    public function test_a_city_the_map_does_not_know_is_rejected(): void
    {
        $graduate = $this->graduate(['city' => 'Angeles, Pampanga']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.deployments.city', $graduate), ['city' => 'Atlantis'])
            ->assertRedirect()
            ->assertSessionHasErrors('city', null, "city_{$graduate->id}");

        $this->assertSame('Angeles, Pampanga', $graduate->refresh()->city);
    }

    public function test_regional_admin_cannot_set_another_regions_graduates_city(): void
    {
        $graduate = $this->graduate();

        $this->actingAs(User::factory()->admin('Region IV-A')->create())
            ->post(route('admin.deployments.city', $graduate), ['city' => 'Malolos, Bulacan'])
            ->assertForbidden();
    }

    public function test_only_graduates_get_a_city_here(): void
    {
        $participant = User::factory()->create(['region' => 'Region III']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.deployments.city', $participant), ['city' => 'Malolos, Bulacan'])
            ->assertNotFound();
    }

    public function test_the_list_can_be_filtered_to_graduates_the_map_cannot_place(): void
    {
        $placed = $this->graduate(['name' => 'Placed Person', 'city' => 'Angeles, Pampanga']);
        $blank = $this->graduate(['name' => 'Blank Person', 'city' => null]);
        $unknown = $this->graduate(['name' => 'Unknown Person', 'city' => 'Demo City']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.deployments.index', ['status' => 'no_city']))
            ->assertOk()
            ->assertSee($blank->name)
            ->assertSee($unknown->name)
            ->assertDontSee($placed->name);
    }

    public function test_city_totals_count_graduates_deployed_people_and_trainings_per_city(): void
    {
        $deployed = User::factory()->create(['region' => 'Region III', 'city' => 'Angeles, Pampanga']);
        $neighbour = User::factory()->create(['region' => 'Region III', 'city' => 'Angeles City, Pampanga']);
        $elsewhere = User::factory()->create(['region' => 'Region III', 'city' => 'Malolos, Bulacan']);
        $nowhere = User::factory()->create(['region' => 'Region III', 'city' => null]);

        ParticipantDeployment::create([
            'user_id' => $deployed->id,
            'deployment' => 'Mayon Operations',
            'deployment_date' => now()->subWeek()->toDateString(),
        ]);
        ParticipantDeployment::create([
            'user_id' => $deployed->id,
            'deployment' => 'Taal Operations',
            'deployment_date' => now()->subDay()->toDateString(),
        ]);

        $first = $this->training([$deployed, $neighbour, $nowhere]);
        $second = $this->training([$deployed, $elsewhere]);

        $totals = MonitoringController::cityTotals(collect([$first, $second]));
        $byName = collect($totals['cities'])->keyBy('name');

        // Two people, three completions between them, one of them deployed
        // (twice, but they're one person), across both trainings.
        $this->assertSame(
            ['graduates' => 3, 'deployed' => 1, 'trainings' => 2],
            collect($byName['Angeles City'])->only(['graduates', 'deployed', 'trainings'])->all(),
        );
        $this->assertSame(1, $byName['City of Malolos']['graduates']);
        $this->assertSame(0, $byName['City of Malolos']['deployed']);

        // The person with no City is counted, just not on the map.
        $this->assertSame(1, $totals['unplaced']);
        $this->assertSame(1, $totals['unplaced_people']);
    }
}
