<?php

namespace Tests\Feature;

use App\Models\ParticipantEvaluation;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The Tools page: the Evaluation Computation browser, and the CSV import for
 * the training data the regions send in. The old ATAR card (blank template +
 * one PDF upload per training) was removed on 2026-10-19; the CSV import moved
 * here from the ATAR page on the same day, because the CSV is regional training
 * data, not an ATAR — the ATAR page is the narrative reports alone now.
 */
class ToolsEvaluationComputationTest extends TestCase
{
    use RefreshDatabase;

    private function evaluatedTraining(string $title, string $region = 'Region III', ?string $venue = null): TrainingRequest
    {
        $trainingRequest = new TrainingRequest([
            'training_slug' => str($title)->slug()->value(),
            'training_title' => $title,
            'region' => $region,
            'requesting_agency' => 'Municipality of Fictional Town',
            'contact_person' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'contact_email' => 'mdrrmo@fictionaltown.gov.ph',
            'number_of_participants' => 30,
            'preferred_date' => now()->subMonth()->toDateString(),
            'venue' => $venue ?? "{$title} Venue",
            'purpose' => 'Newly organized council.',
            'status' => TrainingRequest::STATUS_COMPLETED,
        ]);
        $trainingRequest->save();

        $participant = User::factory()->create(['region' => $region]);
        $trainingRequest->participants()->sync([$participant->id]);
        ParticipantEvaluation::create([
            'training_request_id' => $trainingRequest->id,
            'user_id' => $participant->id,
            'module_ratings' => [
                ['module' => 'M1 - Orientation', 'module_rating' => 5, 'trainer_rating' => 4, 'comment' => 'Clear and well paced.'],
            ],
        ]);

        return $trainingRequest;
    }

    public function test_the_atar_section_and_its_routes_are_gone(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.tools'))
            ->assertOk()
            ->assertSee('Evaluation Computation (L1 / L2)')
            ->assertDontSee('ATAR Template')
            // The search box and upload cells that belonged to that card.
            ->assertDontSee('files_q', false);

        $this->assertFalse(Route::has('admin.tools.files'));
        $this->assertFalse(Route::has('admin.tools.atar-template'));
    }

    public function test_each_training_is_listed_once_with_its_sessions_and_a_link_to_the_full_summary(): void
    {
        $first = $this->evaluatedTraining('Water Search and Rescue');
        $this->evaluatedTraining('Fire Safety Orientation');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.tools'))
            ->assertOk()
            // Both trainings in the picker, the selected one's session beside it.
            ->assertSee('Water Search and Rescue')
            ->assertSee('Fire Safety Orientation')
            ->assertSee('Water Search and Rescue Venue')
            ->assertSee('1/1')
            // Each session also opens on the read-only summary page.
            ->assertSee(route('admin.summary.evaluations', $first), false);
    }

    /**
     * The training list is not paged — every training is listed and the list
     * scrolls. It is a training's sessions that are paged, ten at a time, in
     * the browser: the collapsed rows are all in the page already (only the
     * expanded breakdowns are fetched), so paging needs no reload.
     */
    public function test_every_training_is_listed_and_the_sessions_are_paged_ten_at_a_time(): void
    {
        foreach (range(1, 12) as $number) {
            $this->evaluatedTraining(sprintf('Training %02d', $number));
        }

        // One training with enough sessions to need a second page.
        foreach (range(1, 11) as $number) {
            $this->evaluatedTraining('Busy Training', 'Region III', sprintf('Venue %02d', $number));
        }

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.tools'));

        $response->assertOk()
            // Every title, not just the first ten.
            ->assertSee('Training 01')
            ->assertSee('Training 12')
            ->assertSee('13 trainings')
            // Ten per page, with a two-page pager for the training that has eleven
            // sessions and a single page for the ones that have one.
            ->assertSee('perPage: 10', false)
            ->assertSee('Venue 11')
            ->assertSee('pages: 2', false)
            ->assertSee('pages: 1', false);
    }

    /**
     * The search runs in SQL over every title, so it is not limited to what the
     * list happens to be scrolled to.
     */
    public function test_searching_narrows_the_training_list(): void
    {
        foreach (range(1, 12) as $number) {
            $this->evaluatedTraining(sprintf('Training %02d', $number));
        }

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.tools', ['trainings_q' => 'Training 12']))
            ->assertOk()
            ->assertSee('Training 12')
            ->assertDontSee('Training 01');
    }

    public function test_the_csv_import_is_on_tools_and_no_longer_under_atar(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('admin.tools'))
            ->assertOk()
            ->assertSee('Import Training Data (CSV)')
            ->assertSee(route('admin.atar-records.import'), false)
            ->assertSee(route('admin.atar-records.import.template'), false);

        // The ATAR page is the narrative reports alone — no CSV tab.
        $this->actingAs($superAdmin)
            ->get(route('admin.atar-reports.index'))
            ->assertOk()
            ->assertSee('ATAR Reports')
            ->assertDontSee('Records (CSV Import)')
            ->assertDontSee(route('admin.atar-records.index'), false);
    }

    public function test_the_import_stays_super_admin_only(): void
    {
        $regionalAdmin = User::factory()->admin('Region III')->create();

        $this->actingAs($regionalAdmin)
            ->get(route('admin.tools'))
            ->assertOk()
            ->assertDontSee('Import Training Data (CSV)');

        $this->actingAs($regionalAdmin)
            ->get(route('admin.atar-records.import'))
            ->assertForbidden();
    }

    public function test_a_regional_admin_only_sees_their_own_regions_trainings(): void
    {
        $this->evaluatedTraining('Water Search and Rescue', 'Region III');
        $this->evaluatedTraining('Fire Safety Orientation', 'Region I');

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.tools'))
            ->assertOk()
            ->assertSee('Water Search and Rescue')
            ->assertDontSee('Fire Safety Orientation');
    }
}
