<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Instructor;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A Regional Admin sees their region's data and changes none of it —
 * scheduling, approving, evaluations, instructors, and certificate issuance
 * all belong to the Super Admin (decision recorded 2026-09-22).
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

    public function test_regional_admin_cannot_edit_evaluations_upload_files_or_manage_instructors(): void
    {
        Storage::fake('public');
        $record = $this->request();
        $instructor = Instructor::create(['name' => 'Ana Reyes', 'region' => 'Region III', 'training_type' => 'CBDRRM']);
        $admin = $this->regionalAdmin();

        $this->actingAs($admin)->put(route('admin.evaluations.update', $record), [])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.tools.files', $record), [])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.instructors.store'), ['name' => 'New Person'])->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.instructors.update', $instructor), ['name' => 'Renamed'])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.instructors.certificate', $instructor), [])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.instructors.photo', $instructor), [])->assertForbidden();

        $this->assertSame('Ana Reyes', $instructor->fresh()->name);
        $this->assertSame(1, Instructor::count());
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
            ->assertDontSee('Trainings marked', false);

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

    public function test_regional_admins_still_see_the_rest_of_summary(): void
    {
        $participant = User::factory()->create(['name' => 'Maria Santos', 'region' => 'Region III']);

        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee($participant->name);
    }
}
