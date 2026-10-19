<?php

namespace Tests\Feature;

use App\Models\ParticipantEvaluation;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Summary page's Evaluations card rolls up per training, not per
 * participant (decided 2026-10-19): an admin reads how a training was
 * received, and the per-participant answers live on that training's View
 * page. The Instructors and Registered Participants cards are gone from this
 * page too — the Instructor Roster and Manage Users are where those live.
 */
class SummaryEvaluationsTest extends TestCase
{
    use RefreshDatabase;

    private function training(string $region = 'Region III'): TrainingRequest
    {
        $trainingRequest = new TrainingRequest([
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
            'status' => TrainingRequest::STATUS_COMPLETED,
        ]);
        $trainingRequest->save();

        return $trainingRequest;
    }

    public function test_evaluations_are_listed_once_per_training_with_a_submission_count(): void
    {
        $training = $this->training();
        $participants = User::factory()->count(2)->create(['region' => 'Region III']);
        $training->participants()->sync($participants->pluck('id')->all());

        foreach ($participants as $participant) {
            ParticipantEvaluation::create([
                'training_request_id' => $training->id,
                'user_id' => $participant->id,
                'overall_comments' => "Comment from {$participant->name}",
            ]);
        }

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'));

        $response->assertOk()
            ->assertSee('2 of 2 submitted')
            // One row for the training, not one per participant: the individual
            // answers stay on the training's View page. (Participant names are
            // not asserted against — the Registered Participants card on this
            // same page legitimately lists them.)
            ->assertDontSee("Comment from {$participants->first()->name}")
            ->assertDontSee("Comment from {$participants->last()->name}");
    }

    public function test_a_training_with_no_evaluations_is_left_out(): void
    {
        $this->training();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertSee('No evaluations submitted yet.');
    }

    public function test_view_opens_a_read_only_summary_with_nothing_to_edit(): void
    {
        $training = $this->training();
        $participant = User::factory()->create(['region' => 'Region III']);
        $training->participants()->sync([$participant->id]);
        ParticipantEvaluation::create([
            'training_request_id' => $training->id,
            'user_id' => $participant->id,
            'module_ratings' => [
                ['module' => 'Incident Command System', 'module_rating' => 5, 'trainer_rating' => 4, 'comment' => 'Clear and well paced.'],
            ],
            'overall_comments' => 'Well run.',
        ]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary.evaluations', $training));

        $response->assertOk()
            ->assertSee('Evaluation Summary')
            ->assertSee($training->training_title)
            ->assertSee('Incident Command System')
            // The reviews, read-only: no editable control and nothing that
            // writes. (The only <form> in the markup is the layout's Log Out,
            // so this checks for inputs and the update verb instead.)
            ->assertDontSee('Save Changes')
            ->assertDontSee('<select', false)
            ->assertDontSee('<textarea', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('value="PATCH"', false);
    }

    public function test_a_regional_admin_cannot_open_another_regions_evaluation_summary(): void
    {
        $training = $this->training('Region I');

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.summary.evaluations', $training))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin('Region I')->create())
            ->get(route('admin.summary.evaluations', $training))
            ->assertOk();
    }

    public function test_the_instructors_and_participants_sections_are_gone_from_the_summary_page(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.summary'))
            ->assertOk()
            ->assertDontSee('id="instructors"', false)
            ->assertDontSee('instructors_q', false)
            ->assertDontSee('id="registered-participants"', false)
            ->assertDontSee('participants_q', false);
    }

    public function test_a_regional_admins_summary_page_is_the_evaluations_section_alone(): void
    {
        $response = $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.summary'));

        $response->assertOk()
            ->assertSee('Evaluations')
            ->assertDontSee('id="registered-participants"', false)
            // Trainings stays Super Admin's.
            ->assertDontSee('id="training-requests"', false);
    }
}
