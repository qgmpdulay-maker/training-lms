<?php

namespace Tests\Feature;

use App\Models\ParticipantDeployment;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recording graduate deployments — the one write a Regional Admin has.
 */
class ParticipantDeploymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A participant who has completed a training, so counts as a graduate.
     */
    private function graduate(string $region = 'Region III'): User
    {
        $participant = User::factory()->create(['region' => $region]);

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

        return $participant->refresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'deployment' => 'Mayon Operations',
            'deployment_date' => now()->subWeek()->toDateString(),
            'deployment_role' => 'Team Leader',
        ], $overrides);
    }

    public function test_regional_admin_can_record_a_deployment_for_their_own_graduate(): void
    {
        $graduate = $this->graduate();
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)
            ->post(route('admin.deployments.store', $graduate), $this->payload())
            ->assertRedirect();

        $deployment = ParticipantDeployment::sole();
        $this->assertSame($graduate->id, $deployment->user_id);
        $this->assertSame('Mayon Operations', $deployment->deployment);
        $this->assertSame('Team Leader', $deployment->deployment_role);
        // Who entered it is kept for accountability.
        $this->assertSame($admin->id, $deployment->recorded_by);
    }

    public function test_a_graduate_can_have_several_deployments_newest_first(): void
    {
        $graduate = $this->graduate();
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)->post(route('admin.deployments.store', $graduate), $this->payload([
            'deployment' => 'Mayon Operations', 'deployment_date' => now()->subMonths(3)->toDateString(),
        ]));
        $this->actingAs($admin)->post(route('admin.deployments.store', $graduate), $this->payload([
            'deployment' => 'RDANA Deployment', 'deployment_date' => now()->subWeek()->toDateString(),
        ]));

        $this->assertSame(
            ['RDANA Deployment', 'Mayon Operations'],
            $graduate->refresh()->deployments->pluck('deployment')->all()
        );
    }

    public function test_regional_admin_cannot_record_for_another_region(): void
    {
        $graduate = $this->graduate('Region V');

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.deployments.store', $graduate), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, ParticipantDeployment::count());
    }

    public function test_someone_who_has_not_finished_a_training_is_not_deployable(): void
    {
        $notAGraduate = User::factory()->create(['region' => 'Region III']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.deployments.store', $notAGraduate), $this->payload())
            ->assertNotFound();

        $this->assertSame(0, ParticipantDeployment::count());
    }

    public function test_a_deployment_cannot_be_dated_in_the_future(): void
    {
        $graduate = $this->graduate();

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.deployments.store', $graduate), $this->payload([
                'deployment_date' => now()->addWeek()->toDateString(),
            ]))
            ->assertSessionHasErrors('deployment_date');

        $this->assertSame(0, ParticipantDeployment::count());
    }

    public function test_a_regional_admin_can_remove_a_record_but_not_another_regions(): void
    {
        $own = $this->graduate('Region III');
        $other = $this->graduate('Region V');
        $admin = User::factory()->admin('Region III')->create();

        $ownRecord = $own->deployments()->create($this->payload());
        $otherRecord = $other->deployments()->create($this->payload());

        $this->actingAs($admin)->delete(route('admin.deployments.destroy', $otherRecord))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.deployments.destroy', $ownRecord))->assertRedirect();

        $this->assertNull($ownRecord->fresh());
        $this->assertNotNull($otherRecord->fresh());
    }

    public function test_the_page_lists_only_graduates_from_the_admins_region(): void
    {
        $own = $this->graduate('Region III');
        $other = $this->graduate('Region V');
        $notAGraduate = User::factory()->create(['region' => 'Region III', 'name' => 'Never Trained']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.deployments.index'))
            ->assertOk()
            ->assertSee($own->name)
            ->assertDontSee($other->name)
            ->assertDontSee('Never Trained');
    }

    public function test_super_admin_can_record_for_any_region(): void
    {
        $graduate = $this->graduate('Region V');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.deployments.store', $graduate), $this->payload())
            ->assertRedirect();

        $this->assertSame(1, ParticipantDeployment::count());
    }

    public function test_participants_cannot_reach_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.deployments.index'))
            ->assertForbidden();
    }

    public function test_the_list_can_be_filtered_to_who_still_needs_recording(): void
    {
        $deployed = $this->graduate();
        $deployed->update(['name' => 'Already Deployed']);
        $deployed->deployments()->create($this->payload());

        $pending = $this->graduate();
        $pending->update(['name' => 'Needs Recording']);

        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)
            ->get(route('admin.deployments.index', ['status' => 'not_deployed']))
            ->assertOk()
            ->assertSee('Needs Recording')
            ->assertDontSee('Already Deployed');

        $this->actingAs($admin)
            ->get(route('admin.deployments.index', ['status' => 'deployed']))
            ->assertOk()
            ->assertSee('Already Deployed')
            ->assertDontSee('Needs Recording');
    }

    public function test_the_search_returns_just_the_list_fragment(): void
    {
        $graduate = $this->graduate();
        $graduate->update(['name' => 'Maria Santos']);

        $response = $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.deployments.index', ['q' => 'Maria', '_section' => 'graduates']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk()->assertSee('Maria Santos');
        // A fragment, not the whole page — that's what keeps the page from
        // reloading and losing the admin's scroll position.
        $response->assertDontSee('Graduate Deployments');
    }
}
