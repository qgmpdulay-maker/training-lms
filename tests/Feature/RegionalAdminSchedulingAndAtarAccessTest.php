<?php

namespace Tests\Feature;

use App\Models\AtarReport;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two things a Regional Admin owns have to be reachable from their own
 * side of the app (2026-10-19): they schedule their region's trainings, and
 * they write the ATAR for each one afterwards.
 *
 * The behaviour behind both is covered by TrainingSchedulingTest and
 * AtarReportAuthoringTest — this is about them being *there*: in the nav, and
 * on the training itself.
 */
class RegionalAdminSchedulingAndAtarAccessTest extends TestCase
{
    use RefreshDatabase;

    private function training(array $overrides = []): TrainingRequest
    {
        $trainingRequest = new TrainingRequest(array_merge([
            'training_slug' => 'water-search-and-rescue',
            'training_title' => 'Water Search and Rescue',
            'region' => 'Region III',
            'requesting_agency' => 'Municipality of Fictional Town',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            'preferred_date' => now()->subMonth()->toDateString(),
            'venue' => 'Municipal Hall',
            'purpose' => 'Newly organized council.',
            'status' => TrainingRequest::STATUS_COMPLETED,
        ], $overrides));
        $trainingRequest->save();

        return $trainingRequest;
    }

    public function test_a_regional_admin_has_training_and_atar_in_their_nav(): void
    {
        // Any admin page renders the sidebar; the dashboard itself is skipped
        // because its year-over-year queries use SQL the test SQLite driver
        // doesn't implement.
        $response = $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.trainings.index'));

        $response->assertOk()
            ->assertSee(route('admin.trainings.index'), false)
            ->assertSee(route('admin.atar-reports.index'), false);
    }

    /**
     * The Super Admin cannot schedule, and reads every region's trainings on
     * Summary instead, so the entry would only be clutter for them.
     */
    public function test_the_super_admin_does_not_get_the_training_entry(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertDontSee(route('admin.trainings.index'), false)
            // ATAR stays in their nav — they review and finalize what the regions write.
            ->assertSee(route('admin.atar-reports.index'), false);
    }

    /**
     * The Training group reads in the order the work runs — assess, schedule,
     * report — with the rosters and utilities after it.
     */
    public function test_the_nav_is_ordered_the_way_the_work_runs(): void
    {
        $response = $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.trainings.index'));

        $html = $response->assertOk()->getContent();

        $order = collect([
            'needs' => route('admin.training-needs-assessment'),
            'training' => route('admin.trainings.index'),
            'atar' => route('admin.atar-reports.index'),
            'instructors' => route('admin.instructors.index'),
            'deployments' => route('admin.deployments.index'),
            'tools' => route('admin.tools'),
        ])->map(fn ($url) => strpos($html, 'href="'.$url.'"'));

        $this->assertFalse($order->contains(false), 'Every Training nav entry should be present.');
        $this->assertSame($order->values()->sort()->values()->all(), $order->values()->all());
    }

    /** An admin-only page has no business in a participant's nav. */
    public function test_a_participant_is_not_shown_admin_entries(): void
    {
        $this->actingAs(User::factory()->create(['participant_type' => 'LGU']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.atar-reports.index'), false)
            ->assertDontSee(route('admin.trainings.index'), false)
            ->assertDontSee(route('admin.tools'), false);
    }

    public function test_the_training_list_offers_to_schedule_and_to_write_the_atar(): void
    {
        $training = $this->training();

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.trainings.index'))
            ->assertOk()
            ->assertSee('Schedule Training')
            ->assertSee('Create ATAR')
            // Starting one from here arrives with the training already picked.
            ->assertSee(route('admin.atar-reports.create', ['training_request_id' => $training->id]), false);
    }

    public function test_a_training_that_already_has_an_atar_opens_it_instead(): void
    {
        $training = $this->training();
        $report = AtarReport::create([
            'training_request_id' => $training->id,
            'created_by' => User::factory()->admin('Region III')->create()->id,
            'region' => 'Region III',
            'title' => $training->training_title,
            'status' => AtarReport::STATUS_DRAFT,
        ]);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.trainings.index'))
            ->assertOk()
            ->assertSee('Open ATAR')
            ->assertSee(route('admin.atar-reports.edit', $report), false)
            ->assertDontSee('Create ATAR');
    }

    public function test_the_atar_create_page_preselects_the_training_it_was_opened_from(): void
    {
        $training = $this->training();
        // Needs an attendee to be offered at all — an ATAR reports on who attended.
        $training->participants()->sync([User::factory()->create(['region' => 'Region III'])->id]);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.atar-reports.create', ['training_request_id' => $training->id]))
            ->assertOk()
            ->assertSee('value="'.$training->id.'" selected', false);
    }

    public function test_a_training_from_another_region_is_never_preselected(): void
    {
        $training = $this->training(['region' => 'Region I']);
        $training->participants()->sync([User::factory()->create(['region' => 'Region I'])->id]);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.atar-reports.create', ['training_request_id' => $training->id]))
            ->assertOk()
            ->assertDontSee('value="'.$training->id.'" selected', false);
    }

    /**
     * The Super Admin's Summary shares the training table with this page, and
     * writing an ATAR is not theirs — so the action must not appear there.
     */
    public function test_the_atar_action_does_not_show_on_the_super_admins_summary(): void
    {
        $this->training();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee('Water Search and Rescue')
            ->assertDontSee('Create ATAR');
    }
}
