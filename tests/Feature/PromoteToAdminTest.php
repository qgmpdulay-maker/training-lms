<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Promoting a participant to Regional Admin — a privilege grant, so it must
 * not be reachable from a single stray click in the Participants table.
 */
class PromoteToAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_region_is_required_so_a_bare_click_promotes_nobody(): void
    {
        $participant = User::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.promote', $participant), [])
            ->assertSessionHasErrors('region');

        $this->assertSame(User::ROLE_PARTICIPANT, $participant->fresh()->role);
    }

    public function test_an_unknown_region_is_rejected(): void
    {
        $participant = User::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.promote', $participant), ['region' => 'Atlantis'])
            ->assertSessionHasErrors('region');

        $this->assertSame(User::ROLE_PARTICIPANT, $participant->fresh()->role);
    }

    public function test_super_admin_can_promote_with_a_region(): void
    {
        $participant = User::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.promote', $participant), ['region' => 'Region III'])
            ->assertRedirect(route('admin.users.index'));

        $participant->refresh();
        $this->assertSame(User::ROLE_ADMIN, $participant->role);
        $this->assertSame('Region III', $participant->region);
    }

    public function test_an_existing_admin_cannot_be_promoted_again(): void
    {
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.promote', $admin), ['region' => 'NCR'])
            ->assertForbidden();

        $this->assertSame('Region III', $admin->fresh()->region);
    }

    public function test_a_regional_admin_cannot_promote_anyone(): void
    {
        $participant = User::factory()->create();

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.users.promote', $participant), ['region' => 'Region III'])
            ->assertForbidden();

        $this->assertSame(User::ROLE_PARTICIPANT, $participant->fresh()->role);
    }

    public function test_the_row_no_longer_exposes_a_one_click_promote_form(): void
    {
        User::factory()->create(['name' => 'Maria Santos']);

        $html = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Maria Santos')
            ->getContent();

        // The region picker now lives inside the confirmation modal, which
        // names the account and keeps its submit disabled until a region is
        // chosen — so there is no bare "Select region" control in the row.
        $this->assertStringNotContainsString('Select region</option>', $html);
        $this->assertStringContainsString('Select a region', $html);
        $this->assertStringContainsString('Make Regional Admin', $html);
        $this->assertStringContainsString(":disabled=\"region === ''\"", $html);
    }
}
