<?php

namespace Tests\Feature;

use App\Models\AtarReport;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The ATAR is the one write that runs the opposite way round from the rest
 * of the app (see RegionalAdminIsReadOnlyTest for the general rule): the
 * **Regional Admin writes** their region's After Training Activity Report,
 * and the Super Admin reads, edits, and deletes every region's but cannot
 * create one.
 */
class AtarReportAuthoringTest extends TestCase
{
    use RefreshDatabase;

    private function completedTraining(string $region = 'Region III'): TrainingRequest
    {
        $trainingRequest = new TrainingRequest([
            'training_slug' => 'community-based-drrm',
            'training_title' => 'Community-Based Disaster Risk Reduction and Management',
            'region' => $region,
            'requesting_agency' => 'Municipality of Fictional Town',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            'preferred_date' => now()->subMonth()->toDateString(),
            'venue' => 'Municipal Hall',
            'purpose' => 'Newly organized council.',
            'status' => TrainingRequest::STATUS_COMPLETED,
        ]);
        $trainingRequest->save();

        $trainingRequest->participants()->sync([
            User::factory()->create(['region' => $region])->id,
        ]);

        return $trainingRequest;
    }

    public function test_regional_admin_can_create_an_atar_for_their_own_region(): void
    {
        $training = $this->completedTraining();
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)->get(route('admin.atar-reports.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.atar-reports.store'), [
                'mode' => 'generate',
                'training_request_id' => $training->id,
            ])
            ->assertRedirect();

        $report = AtarReport::sole();
        $this->assertSame('Region III', $report->region);
        $this->assertSame($admin->id, $report->created_by);
    }

    public function test_a_from_scratch_atar_takes_the_authors_region(): void
    {
        $admin = User::factory()->admin('Region V')->create();

        $this->actingAs($admin)
            ->post(route('admin.atar-reports.store'), ['mode' => 'blank'])
            ->assertRedirect();

        $this->assertSame('Region V', AtarReport::sole()->region);
    }

    public function test_super_admin_cannot_create_an_atar(): void
    {
        $training = $this->completedTraining();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('admin.atar-reports.create'))->assertForbidden();
        $this->actingAs($superAdmin)
            ->post(route('admin.atar-reports.store'), [
                'mode' => 'generate',
                'training_request_id' => $training->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, AtarReport::count());
    }

    public function test_the_new_atar_button_only_shows_for_the_regional_admin(): void
    {
        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.atar-reports.index'))
            ->assertOk()
            ->assertSee('New ATAR');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.atar-reports.index'))
            ->assertOk()
            ->assertDontSee('New ATAR');
    }

    public function test_a_regional_admin_only_sees_and_edits_their_own_regions_reports(): void
    {
        $mine = AtarReport::create(['title' => 'Region III ATAR', 'region' => 'Region III']);
        $theirs = AtarReport::create(['title' => 'Region V ATAR', 'region' => 'Region V']);
        $admin = User::factory()->admin('Region III')->create();

        $this->actingAs($admin)
            ->get(route('admin.atar-reports.index'))
            ->assertOk()
            ->assertSee('Region III ATAR')
            ->assertDontSee('Region V ATAR');

        $this->actingAs($admin)->get(route('admin.atar-reports.edit', $mine))->assertOk();

        // 404 rather than 403, so poking at ids can't reveal which other
        // regions' reports exist.
        $this->actingAs($admin)->get(route('admin.atar-reports.edit', $theirs))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.atar-reports.pdf', $theirs))->assertNotFound();
        $this->actingAs($admin)
            ->put(route('admin.atar-reports.update', $theirs), ['status' => AtarReport::STATUS_FINAL])
            ->assertNotFound();
    }

    public function test_super_admin_reads_edits_and_deletes_every_region(): void
    {
        $report = AtarReport::create(['title' => 'Region V ATAR', 'region' => 'Region V']);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.atar-reports.index'))
            ->assertOk()
            ->assertSee('Region V ATAR');

        $this->actingAs($superAdmin)->get(route('admin.atar-reports.edit', $report))->assertOk();

        $this->actingAs($superAdmin)
            ->put(route('admin.atar-reports.update', $report), [
                'title' => 'Edited by Central',
                'status' => AtarReport::STATUS_FINAL,
            ])
            ->assertRedirect();

        $this->assertSame('Edited by Central', $report->fresh()->title);

        $this->actingAs($superAdmin)->delete(route('admin.atar-reports.destroy', $report))->assertRedirect();
        $this->assertSame(0, AtarReport::count());
    }

    public function test_deleting_an_atar_stays_with_the_super_admin(): void
    {
        $report = AtarReport::create(['title' => 'Region III ATAR', 'region' => 'Region III']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->delete(route('admin.atar-reports.destroy', $report))
            ->assertForbidden();

        $this->assertSame(1, AtarReport::count());
    }
}
