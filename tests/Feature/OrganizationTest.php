<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Organizations (LGUs / NGAs / academe / teams) and their membership.
 *
 * A Regional Admin adds the bodies in their own region; membership stays
 * super-admin-curated, and nobody self-joins — that's what makes a member
 * trustworthy enough to act for the body.
 */
class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function organization(array $overrides = []): Organization
    {
        return Organization::create(array_merge([
            'name' => 'Municipality of Fictional Town',
            'type' => Organization::TYPE_LGU,
            'region' => 'Region III',
            'city' => 'Fictional Town',
        ], $overrides));
    }

    public function test_super_admin_can_create_an_organization(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.store'), [
                'name' => 'Municipality of Fictional Town',
                'type' => Organization::TYPE_LGU,
                'region' => 'Region III',
                'city' => 'Fictional Town',
            ])
            ->assertRedirect();

        $this->assertSame(1, Organization::count());
        $this->assertSame(Organization::TYPE_LGU, Organization::sole()->type);
    }

    public function test_the_same_body_cannot_be_entered_twice_for_one_type_and_region(): void
    {
        $this->organization();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.store'), [
                'name' => 'Municipality of Fictional Town',
                'type' => Organization::TYPE_LGU,
                'region' => 'Region III',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Organization::count());
    }

    public function test_the_same_name_is_allowed_in_a_different_region(): void
    {
        $this->organization();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.store'), [
                'name' => 'Municipality of Fictional Town',
                'type' => Organization::TYPE_LGU,
                'region' => 'Region V',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Organization::count());
    }

    public function test_super_admin_can_assign_a_member_with_a_position(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.members.add', $organization), [
                'user_ids' => [$user->id],
                'position' => 'DRRM Focal Person',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame($organization->id, $user->organization_id);
        $this->assertSame('DRRM Focal Person', $user->position);
        // "position" is their role in the body; the system role is untouched.
        $this->assertSame(User::ROLE_PARTICIPANT, $user->role);
    }

    public function test_removing_a_member_clears_their_position_too(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create(['organization_id' => $organization->id, 'position' => 'Head']);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.organizations.members.remove', [$organization, $user]))
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->organization_id);
        $this->assertNull($user->position);
    }

    public function test_deleting_an_organization_returns_members_to_unassigned(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.organizations.destroy', $organization))
            ->assertRedirect();

        $this->assertSame(0, Organization::count());
        // The person is kept — only their membership goes.
        $this->assertNotNull($user->fresh());
        $this->assertNull($user->fresh()->organization_id);
    }

    public function test_the_member_picker_only_offers_unassigned_users_and_shows_their_typed_hint(): void
    {
        $organization = $this->organization();
        // Both in the body's own region — the picker is region-scoped, so a
        // candidate from elsewhere wouldn't show regardless of name.
        $alreadyAssigned = User::factory()->create(['name' => 'Already Placed', 'region' => 'Region III', 'organization_id' => $organization->id]);
        $free = User::factory()->create(['name' => 'Maria Santos', 'region' => 'Region III', 'organization' => 'MDRRMO Fictional Town']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.organizations.show', $organization))
            ->assertOk()
            ->assertSee('Maria Santos')
            // Shown so the admin can sanity-check the match, never as proof.
            ->assertSee('MDRRMO Fictional Town')
            ->assertDontSee('value="'.$alreadyAssigned->id.'"', false);
    }

    public function test_a_super_admins_list_spans_every_region_and_links_into_each_body(): void
    {
        $this->organization(['name' => 'Municipality of Fictional Town', 'region' => 'Region III']);
        $elsewhere = $this->organization(['name' => 'Municipality of Elsewhere', 'region' => 'Region V', 'city' => 'Elsewhere']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertSee('Municipality of Fictional Town')
            ->assertSee('Municipality of Elsewhere')
            ->assertSee(__('All Regions'))
            ->assertSee(route('admin.organizations.show', $elsewhere));
    }

    public function test_participants_cannot_reach_organizations_at_all(): void
    {
        $organization = $this->organization();
        $participant = User::factory()->create();

        $this->actingAs($participant)->get(route('admin.organizations.index'))->assertForbidden();
        $this->actingAs($participant)->post(route('admin.organizations.store'), [
            'name' => 'Sneaky LGU', 'type' => Organization::TYPE_LGU,
        ])->assertForbidden();
        $this->actingAs($participant)->post(route('admin.organizations.members.add', $organization), [
            'user_ids' => [$participant->id],
        ])->assertForbidden();

        $this->assertSame(1, Organization::count());
    }

    /**
     * A Regional Admin adds the bodies in their region — they're the ones who
     * know which LGUs and schools exist there — but everything past that stays
     * Super Admin's, membership above all.
     */
    public function test_regional_admin_can_add_an_organization(): void
    {
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Municipality of Fictional Town',
                'type' => Organization::TYPE_LGU,
                'city' => 'Fictional Town',
            ])
            // Sent back to their list, not to the detail page — there is
            // nothing there they're allowed to do.
            ->assertRedirect(route('admin.organizations.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Region III', Organization::sole()->region);
    }

    public function test_a_regional_admin_cannot_file_an_organization_under_another_region(): void
    {
        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.organizations.store'), [
                'name' => 'Municipality of Elsewhere',
                'type' => Organization::TYPE_LGU,
                'region' => 'Region V',
            ])
            ->assertSessionHasNoErrors();

        // Their own region wins over whatever the form posted.
        $this->assertSame('Region III', Organization::sole()->region);
    }

    /**
     * The forced region has to be in place *before* validation, or the
     * name-uniqueness rule (scoped by type and region) would compare against
     * a null region and let the same body in twice.
     */
    public function test_a_regional_admin_cannot_add_the_same_body_twice(): void
    {
        $this->organization(['region' => 'Region III']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.organizations.store'), [
                'name' => 'Municipality of Fictional Town',
                'type' => Organization::TYPE_LGU,
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Organization::count());
    }

    public function test_a_regional_admins_list_is_their_own_region_only(): void
    {
        $this->organization(['name' => 'Municipality of Fictional Town', 'region' => 'Region III']);
        $this->organization(['name' => 'Municipality of Elsewhere', 'region' => 'Region V', 'city' => 'Elsewhere']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertSee('Municipality of Fictional Town')
            ->assertDontSee('Municipality of Elsewhere')
            // No region picker and no link into the super-admin detail page.
            ->assertDontSee(__('All Regions'))
            ->assertDontSee(route('admin.organizations.show', Organization::first()));
    }

    public function test_a_regional_admin_cannot_edit_remove_or_staff_an_organization(): void
    {
        $organization = $this->organization();
        $member = User::factory()->create(['region' => 'Region III']);
        $admin = User::factory()->admin('Region III')->create();

        // Their own region, and still no — these are Super Admin decisions.
        $this->actingAs($admin)->get(route('admin.organizations.show', $organization))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.organizations.update', $organization), [
            'name' => 'Renamed', 'type' => Organization::TYPE_LGU,
        ])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.organizations.members.add', $organization), [
            'user_ids' => [$member->id],
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.organizations.destroy', $organization))->assertForbidden();

        $this->assertSame('Municipality of Fictional Town', $organization->fresh()->name);
        $this->assertNull($member->fresh()->organization_id);
    }

    public function test_a_regional_admin_reaches_organizations_from_the_tools_page(): void
    {
        // No sidebar entry of their own — the nav is crowded enough — so the
        // card on Tools is the way in. If this link goes, so does the feature.
        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.tools'))
            ->assertOk()
            ->assertSee(route('admin.organizations.index'));
    }

    public function test_the_users_page_suggests_a_match_from_what_they_typed_at_signup(): void
    {
        $organization = $this->organization(['name' => 'Municipality of Fictional Town']);
        User::factory()->create(['name' => 'Maria Santos', 'organization' => 'Municipality of Fictional Town']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index'))
            ->assertOk()
            // Pre-selected in the picker, but still needs confirming.
            ->assertSee('<option value="'.$organization->id.'"'.PHP_EOL.'                                                selected>', false);
    }

    public function test_super_admin_can_assign_an_organization_from_the_users_page(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.assign-organization', $user), [
                'organization_id' => $organization->id,
                'position' => 'MDRRM Officer',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame($organization->id, $user->organization_id);
        $this->assertSame('MDRRM Officer', $user->position);
    }

    public function test_clearing_the_organization_also_clears_the_position(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create(['organization_id' => $organization->id, 'position' => 'Head']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.assign-organization', $user), ['organization_id' => '', 'position' => 'Head'])
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->organization_id);
        $this->assertNull($user->position);
    }

    public function test_only_super_admin_can_assign_an_organization(): void
    {
        $organization = $this->organization();
        $user = User::factory()->create();

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.users.assign-organization', $user), ['organization_id' => $organization->id])
            ->assertForbidden();

        $this->assertNull($user->fresh()->organization_id);
    }

    public function test_the_member_picker_only_searches_the_bodys_own_region(): void
    {
        $organization = $this->organization(['region' => 'Region III']);
        User::factory()->create(['name' => 'Maria Santos', 'region' => 'Region III']);
        User::factory()->create(['name' => 'Maria Reyes', 'region' => 'Region V']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.organizations.show', $organization).'?candidates_q=Maria')
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertDontSee('Maria Reyes');
    }

    public function test_a_body_with_no_region_searches_everyone(): void
    {
        $organization = $this->organization(['region' => null]);
        User::factory()->create(['name' => 'Maria Santos', 'region' => 'Region III']);
        User::factory()->create(['name' => 'Maria Reyes', 'region' => 'Region V']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.organizations.show', $organization).'?candidates_q=Maria')
            ->assertOk()
            ->assertSee('Maria Santos')
            ->assertSee('Maria Reyes');
    }

    public function test_the_member_search_returns_just_the_results_fragment(): void
    {
        $organization = $this->organization();
        User::factory()->create(['name' => 'Maria Santos', 'region' => 'Region III']);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.organizations.show', $organization).'?candidates_q=Maria&_section=candidates', [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk()->assertSee('Maria Santos');
        // A fragment, not the whole page — that's what keeps the page from
        // reloading and losing the admin's scroll position.
        $response->assertDontSee('</x-app-layout>', false);
        $response->assertDontSee('Remove Organization');
    }

    public function test_several_people_can_be_added_in_one_batch(): void
    {
        $organization = $this->organization();
        $first = User::factory()->create(['region' => 'Region III']);
        $second = User::factory()->create(['region' => 'Region III']);
        $untouched = User::factory()->create(['region' => 'Region III']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.members.add', $organization), [
                'user_ids' => [$first->id, $second->id],
                'position' => 'Member',
            ])
            ->assertRedirect();

        $this->assertSame($organization->id, $first->fresh()->organization_id);
        $this->assertSame($organization->id, $second->fresh()->organization_id);
        $this->assertSame('Member', $second->fresh()->position);
        $this->assertNull($untouched->fresh()->organization_id);
    }

    public function test_saving_with_nobody_ticked_is_rejected(): void
    {
        $organization = $this->organization();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.members.add', $organization), ['user_ids' => []])
            ->assertSessionHasErrors('user_ids');

        $this->assertSame(0, $organization->fresh()->members()->count());
    }

    public function test_candidates_are_listed_without_searching_first(): void
    {
        $organization = $this->organization(['region' => 'Region III']);
        User::factory()->create(['name' => 'Maria Santos', 'region' => 'Region III']);
        User::factory()->create(['name' => 'Elsewhere Person', 'region' => 'Region V']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.organizations.show', $organization))
            ->assertOk()
            // A tick box per candidate, not an Add button per row.
            ->assertSee('name="user_ids[]"', false)
            ->assertSee('Maria Santos')
            ->assertDontSee('Elsewhere Person');
    }
}
