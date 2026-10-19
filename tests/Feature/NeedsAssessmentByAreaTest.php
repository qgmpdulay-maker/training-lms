<?php

namespace Tests\Feature;

use App\Models\TrainingNeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin Needs Assessment page reads per area, not per person (decided
 * 2026-10-19): one participant's answers say little on their own, so the rows
 * are cities — or regions, via the toggle — with the needs they reported.
 */
class NeedsAssessmentByAreaTest extends TestCase
{
    use RefreshDatabase;

    private function assessment(User $user, string $topCategory, ?string $training = null): TrainingNeedsAssessment
    {
        return $user->trainingNeedsAssessments()->create([
            'type' => TrainingNeedsAssessment::TYPE_GENERIC,
            'answers' => [['question' => 'What is your primary role?', 'selected' => 'Response', 'category' => $topCategory, 'points' => 2]],
            'category_scores' => [$topCategory => 2],
            'top_category' => $topCategory,
            'max_hours' => 16,
            'recommended_training_slug' => $training ? str($training)->slug()->value() : null,
            'recommended_training_title' => $training,
        ]);
    }

    public function test_submissions_are_counted_per_city_by_default(): void
    {
        $first = User::factory()->create(['region' => 'Region III', 'city' => 'Malolos']);
        $second = User::factory()->create(['region' => 'Region III', 'city' => 'Malolos']);
        $other = User::factory()->create(['region' => 'Region I', 'city' => 'Laoag']);

        $this->assessment($first, 'Emergency Response', 'Search and Rescue Operations');
        $this->assessment($second, 'Emergency Response', 'Search and Rescue Operations');
        $this->assessment($other, 'Community Resilience');

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.training-needs-assessment'));

        $response->assertOk()
            ->assertSee('City')
            ->assertSee('Malolos')
            ->assertSee('2 assessments')
            ->assertSee('from 2 participants')
            ->assertSee('Emergency Response')
            ->assertSee('Search and Rescue Operations')
            ->assertSee('Laoag')
            // Not person by person any more.
            ->assertDontSee($first->name)
            ->assertDontSee($first->email);
    }

    public function test_the_toggle_rolls_the_cities_up_into_regions(): void
    {
        $malolos = User::factory()->create(['region' => 'Region III', 'city' => 'Malolos']);
        $cabanatuan = User::factory()->create(['region' => 'Region III', 'city' => 'Cabanatuan']);

        $this->assessment($malolos, 'Emergency Response');
        $this->assessment($cabanatuan, 'Emergency Response');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.training-needs-assessment', ['group' => 'region']))
            ->assertOk()
            ->assertSee('Region III')
            // Both cities' assessments in one regional row.
            ->assertSee('2 assessments')
            ->assertDontSee('Malolos')
            ->assertDontSee('Cabanatuan');
    }

    public function test_a_participant_with_no_city_on_file_is_still_counted(): void
    {
        $user = User::factory()->create(['region' => 'Region III', 'city' => null]);
        $this->assessment($user, 'DRRM Core');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.training-needs-assessment'))
            ->assertOk()
            ->assertSee('Not specified')
            ->assertSee('1 assessment');
    }

    public function test_a_regional_admin_only_sees_their_own_regions_areas(): void
    {
        $own = User::factory()->create(['region' => 'Region III', 'city' => 'Malolos']);
        $other = User::factory()->create(['region' => 'Region I', 'city' => 'Laoag']);

        $this->assessment($own, 'Emergency Response');
        $this->assessment($other, 'Community Resilience');

        $this->actingAs(User::factory()->admin('Region III')->create())
            ->get(route('admin.training-needs-assessment'))
            ->assertOk()
            ->assertSee('Malolos')
            ->assertDontSee('Laoag');
    }
}
