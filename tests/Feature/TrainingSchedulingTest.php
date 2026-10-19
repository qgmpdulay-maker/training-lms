<?php

namespace Tests\Feature;

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Scheduling a training belongs to the Regional Admin (decided 2026-10-19),
 * the same way round as the ATAR (see AtarReportAuthoringTest): they are
 * already authorised to run trainings in their region, so there is **no
 * approval step** — what they save is scheduled. The Super Admin cannot
 * create one; they view every region's, edit them, and remain the only one
 * who marks a training Completed.
 */
class TrainingSchedulingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'training_type' => 'custom',
            'custom_training_title' => 'Basic Life Support',
            'region' => 'Region III',
            'requesting_agency' => 'Municipality of Fictional Town',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            // A Monday — the form refuses weekends.
            'preferred_date' => now()->addMonth()->next('Monday')->toDateString(),
            'venue' => 'Municipal Hall',
            'purpose' => 'Newly organized council.',
        ], $overrides);
    }

    private function scheduled(string $region = 'Region III', array $overrides = []): TrainingRequest
    {
        $trainingRequest = new TrainingRequest(array_merge([
            'training_slug' => 'basic-life-support',
            'training_title' => 'Basic Life Support',
            'region' => $region,
            'requesting_agency' => 'Municipality of Fictional Town',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            'preferred_date' => now()->addMonth()->toDateString(),
            'venue' => 'Municipal Hall',
            'purpose' => 'Newly organized council.',
            'status' => TrainingRequest::STATUS_APPROVED,
        ], $overrides));
        $trainingRequest->save();

        return $trainingRequest;
    }

    public function test_a_regional_admin_schedules_a_training_with_no_approval_step(): void
    {
        Mail::fake();

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->post(route('admin.trainings.store'), $this->form())
            ->assertRedirect(route('admin.trainings.index'));

        $record = TrainingRequest::sole();

        // Scheduled, not pending anyone's approval.
        $this->assertSame(TrainingRequest::STATUS_APPROVED, $record->status);
        $this->assertSame(TrainingRequest::SOURCE_ADMIN_SCHEDULED, $record->source);
        $this->assertNotNull($record->reference_number);
    }

    public function test_scheduling_is_locked_to_the_regional_admins_own_region(): void
    {
        Mail::fake();

        $this->actingAs(User::factory()->admin('Region V')->create())
            ->post(route('admin.trainings.store'), $this->form(['region' => 'Region III']))
            ->assertRedirect();

        $this->assertSame('Region V', TrainingRequest::sole()->region);
    }

    public function test_a_super_admin_cannot_schedule_a_training(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('admin.trainings.create'))->assertForbidden();
        $this->actingAs($superAdmin)->post(route('admin.trainings.store'), $this->form())->assertForbidden();
        $this->actingAs($superAdmin)->get(route('admin.trainings.participants'))->assertForbidden();

        $this->assertSame(0, TrainingRequest::count());
    }

    public function test_the_schedule_button_only_shows_for_the_regional_admin(): void
    {
        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.trainings.index'))
            ->assertOk()
            ->assertSee('Schedule Training');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.trainings.index'))
            ->assertOk()
            ->assertDontSee('Schedule Training');
    }

    public function test_the_calendars_schedule_button_follows_the_same_rule(): void
    {
        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.calendar'))
            ->assertOk()
            ->assertSee('Schedule a Training');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.calendar'))
            ->assertOk()
            ->assertDontSee('Schedule a Training');
    }

    public function test_the_training_list_is_scoped_to_a_regional_admins_region(): void
    {
        $this->scheduled('Region III', ['training_title' => 'Region III Training']);
        $this->scheduled('Region V', ['training_title' => 'Region V Training']);

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.trainings.index'))
            ->assertOk()
            ->assertSee('Region III Training')
            ->assertDontSee('Region V Training');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.trainings.index'))
            ->assertOk()
            ->assertSee('Region III Training')
            ->assertSee('Region V Training');
    }

    /**
     * The Summary page's Trainings card has no status filter (2026-10-19):
     * with no approval step, a scheduled training is already Approved, and the
     * old filter defaulted to Submitted — which hid exactly those trainings.
     */
    public function test_the_summary_lists_trainings_of_every_status_with_no_filter(): void
    {
        $this->scheduled('Region III', ['training_title' => 'Approved Training', 'status' => TrainingRequest::STATUS_APPROVED]);
        $this->scheduled('Region III', ['training_title' => 'Completed Training', 'status' => TrainingRequest::STATUS_COMPLETED]);
        $this->scheduled('Region III', ['training_title' => 'Submitted Training', 'status' => TrainingRequest::STATUS_SUBMITTED]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee('Approved Training')
            ->assertSee('Completed Training')
            ->assertSee('Submitted Training')
            ->assertDontSee('All statuses');
    }

    public function test_the_super_admin_can_still_edit_a_training_they_did_not_create(): void
    {
        $record = $this->scheduled();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->patch(route('admin.summary.update', $record), [
                'status' => TrainingRequest::STATUS_APPROVED,
                'preferred_date' => $record->preferred_date->toDateString(),
                'venue' => 'Moved to the Provincial Capitol',
            ])
            ->assertRedirect(route('admin.summary'));

        $this->assertSame('Moved to the Provincial Capitol', $record->fresh()->venue);
    }
}
