<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Instructor;
use App\Models\ParticipantEvaluation;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Regional Admin sees their region's data and changes none of it —
 * scheduling, approving, evaluations, instructors, and certificate issuance
 * all belong to the Super Admin (decision recorded 2026-09-22).
 *
 * Two deliberate exceptions, each covered by its own file: their graduates'
 * deployments and map city (ParticipantDeploymentTest), and writing their
 * region's ATAR (AtarReportAuthoringTest) — which the Super Admin in turn
 * cannot do. Moving an admin or instructor between regions stays Super Admin
 * only (RegionTransferTest).
 */
class RegionalAdminIsReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function request(array $overrides = []): TrainingRequest
    {
        $trainingRequest = new TrainingRequest(array_merge([
            'training_slug' => 'community-based-drrm',
            'training_title' => 'Community-Based Disaster Risk Reduction and Management',
            'region' => 'Region III',
            'requesting_agency' => 'Municipality of Fictional Town',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            'preferred_date' => now()->addMonth()->toDateString(),
            'venue' => 'Municipal Hall',
            'purpose' => 'Newly organized council.',
            'status' => TrainingRequest::STATUS_SUBMITTED,
        ], $overrides));
        $trainingRequest->save();

        return $trainingRequest;
    }

    private function regionalAdmin(string $region = 'Region III'): User
    {
        return User::factory()->admin($region)->create();
    }

    public function test_regional_admin_cannot_approve_a_request(): void
    {
        $record = $this->request();

        $this->actingAs($this->regionalAdmin())
            ->patch(route('admin.summary.update', $record), [
                'status' => TrainingRequest::STATUS_APPROVED,
                'preferred_date' => $record->preferred_date->toDateString(),
                'venue' => $record->venue,
            ])
            ->assertForbidden();

        $this->assertSame(TrainingRequest::STATUS_SUBMITTED, $record->fresh()->status);
    }

    public function test_regional_admin_cannot_complete_a_request_or_issue_certificates(): void
    {
        $participant = User::factory()->create(['region' => 'Region III']);
        $record = $this->request();
        $record->participants()->sync([$participant->id]);

        $this->actingAs($this->regionalAdmin())
            ->patch(route('admin.summary.update', $record), [
                'status' => TrainingRequest::STATUS_COMPLETED,
                'preferred_date' => $record->preferred_date->toDateString(),
                'venue' => $record->venue,
            ])
            ->assertForbidden();

        $this->assertSame(TrainingRequest::STATUS_SUBMITTED, $record->fresh()->status);
        $this->assertSame(0, Certificate::count());
    }

    public function test_regional_admin_cannot_edit_evaluations(): void
    {
        $record = $this->request();

        $this->actingAs($this->regionalAdmin())
            ->put(route('admin.evaluations.update', $record), [])
            ->assertForbidden();
    }

    public function test_regional_admin_cannot_move_an_instructor_between_regions(): void
    {
        // Maintaining their own region's roster is theirs (see
        // InstructorRosterTest); posting someone out of it is not.
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => 'Region III']);

        $this->actingAs($this->regionalAdmin())
            ->patch(route('admin.instructors.region', $instructor), ['region' => 'Region V'])
            ->assertForbidden();

        $this->assertSame('Region III', $instructor->fresh()->region);
    }

    public function test_regional_admin_can_still_read_their_region(): void
    {
        $record = $this->request();
        $admin = $this->regionalAdmin();

        $this->actingAs($admin)->get(route('admin.summary'))->assertOk();
        $this->actingAs($admin)->get(route('admin.summary.edit', $record))->assertOk();
        $this->actingAs($admin)->get(route('admin.calendar'))->assertOk();
        $this->actingAs($admin)->get(route('admin.instructors.index'))->assertOk();
    }

    public function test_the_read_only_view_hides_the_save_button(): void
    {
        $record = $this->request();

        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.summary.edit', $record))
            ->assertOk()
            ->assertSee('view-only access', false)
            ->assertDontSee('Save Changes');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary.edit', $record))
            ->assertOk()
            ->assertSee('Save Changes')
            ->assertDontSee('view-only access', false);
    }

    public function test_super_admin_can_still_approve_and_complete(): void
    {
        $participant = User::factory()->create(['region' => 'Region III']);
        $record = $this->request();
        $record->participants()->sync([$participant->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.summary.update', $record), [
                'status' => TrainingRequest::STATUS_APPROVED,
                'preferred_date' => $record->preferred_date->toDateString(),
                'venue' => $record->venue,
            ])
            ->assertRedirect(route('admin.summary'));

        $this->assertSame(TrainingRequest::STATUS_APPROVED, $record->fresh()->status);
    }

    public function test_the_trainings_list_is_super_admin_only(): void
    {
        $record = $this->request();

        // Scheduling trainings and marking them Completed — which issues the
        // certificates — belongs to the Super Admin, so the list lives on
        // their Summary only.
        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertDontSee($record->training_title)
            ->assertDontSee('id="training-requests"', false);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee($record->training_title);
    }

    public function test_regional_admins_cannot_fetch_the_trainings_fragment_either(): void
    {
        $this->request();

        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.summary', ['_section' => 'training-requests']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertForbidden();
    }

    /**
     * What's left of their Summary page is the Evaluations section — the
     * Registered Participants and Instructors cards were removed from it
     * (2026-10-19), so this is the one thing they read here, and it stays
     * scoped to their own region.
     */
    public function test_regional_admins_still_see_their_regions_evaluations(): void
    {
        $ownRegion = $this->request(['training_title' => 'Water Search and Rescue']);
        $otherRegion = $this->request(['region' => 'Region I', 'training_title' => 'Fire Safety Orientation']);

        foreach ([$ownRegion, $otherRegion] as $training) {
            $participant = User::factory()->create(['region' => $training->region]);
            $training->participants()->sync([$participant->id]);
            ParticipantEvaluation::create([
                'training_request_id' => $training->id,
                'user_id' => $participant->id,
                'overall_comments' => 'Well run.',
            ]);
        }

        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee($ownRegion->training_title)
            ->assertDontSee($otherRegion->training_title);
    }
}
