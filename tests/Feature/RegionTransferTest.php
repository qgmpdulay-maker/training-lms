<?php

namespace Tests\Feature;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff move between regional offices, and instructors get posted to another
 * one (or sent to run a training outside their own). Both are the Super
 * Admin's to record — region is what scopes everything a Regional Admin can
 * see, so nobody may move themselves or anyone else.
 */
class RegionTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_transfers_a_regional_admin_to_another_region(): void
    {
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.users.region', $admin), ['region' => 'Region V'])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status');

        $this->assertSame('Region V', $admin->fresh()->region);
    }

    public function test_only_a_region_on_the_official_list_is_accepted(): void
    {
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.users.region', $admin), ['region' => 'Region XYZ'])
            ->assertSessionHasErrors('region');

        $this->assertSame('Region III', $admin->fresh()->region);
    }

    public function test_a_participant_has_no_region_to_transfer(): void
    {
        $participant = User::factory()->create(['region' => 'Region III']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.users.region', $participant), ['region' => 'Region V'])
            ->assertForbidden();
    }

    public function test_a_regional_admin_cannot_move_themselves_or_anyone_else(): void
    {
        $admin = User::factory()->admin('Region III')->create();
        $colleague = User::factory()->admin('Region V')->create();

        $this->actingAs($admin)->patch(route('admin.users.region', $admin), ['region' => 'NCR'])->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.users.region', $colleague), ['region' => 'NCR'])->assertForbidden();

        $this->assertSame('Region III', $admin->fresh()->region);
        $this->assertSame('Region V', $colleague->fresh()->region);
    }

    public function test_moving_an_instructor_takes_their_whole_record_with_them(): void
    {
        // An instructor is one row with their courses hanging off it, so the
        // courses follow the person rather than needing to be moved too.
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => 'Region III']);
        $instructor->trainingTypes()->createMany([
            ['training_type' => 'ICS'],
            ['training_type' => 'CBDRRM'],
        ]);
        $other = Instructor::create(['name' => 'Ben Cruz', 'region' => 'Region III']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.instructors.region', $instructor), ['region' => 'Region V'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('Region V', $instructor->fresh()->region);
        $this->assertSame(2, $instructor->trainingTypes()->count());
        $this->assertSame('Region III', $other->fresh()->region);
    }

    public function test_a_regional_admin_cannot_post_an_instructor_in_or_out_of_their_region(): void
    {
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => 'Region III']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->patch(route('admin.instructors.region', $instructor), ['region' => 'Region V'])
            ->assertForbidden();

        $this->assertSame('Region III', $instructor->fresh()->region);
    }

    public function test_the_region_picker_shows_on_the_admins_table_and_instructor_profile(): void
    {
        $admin = User::factory()->admin('Region III')->create();
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => 'Region III']);
        $instructor->trainingTypes()->create(['training_type' => 'ICS']);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.region', $admin), false);

        $this->actingAs($superAdmin)
            ->get(route('admin.instructors.show', $instructor))
            ->assertOk()
            ->assertSee(route('admin.instructors.region', $instructor), false);
    }
}
