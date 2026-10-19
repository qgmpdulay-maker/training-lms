<?php

namespace Tests\Feature;

use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Regional Admin's month calendar: a real wall-calendar grid with each
 * training drawn as a bar across every day it runs.
 *
 * Most of what can go wrong here is arithmetic — which column a bar starts
 * in, how many it spans, and what happens at a week or month boundary — so
 * the assertions go at the rendered `grid-column` rather than at the picture.
 */
class CalendarMonthGridTest extends TestCase
{
    use RefreshDatabase;

    private function regionalAdmin(string $region = 'Region III'): User
    {
        return User::factory()->admin($region)->create();
    }

    private function training(array $overrides = []): TrainingRequest
    {
        $training = new TrainingRequest(array_merge([
            'training_slug' => 'incident-command-system',
            'training_title' => 'Incident Command System',
            'category' => TrainingRequest::CATEGORY_TA,
            'requesting_agency' => 'Bulacan PDRRMO',
            'contact_person' => 'Maria Santos',
            'contact_number' => '09170000000',
            'contact_email' => 'maria@example.test',
            'number_of_participants' => 20,
            'preferred_date' => '2026-11-02',
            'venue' => 'Malolos',
            'purpose' => 'Testing',
            'region' => 'Region III',
        ], $overrides));

        $training->user_id = User::factory()->create()->id;
        $training->status = TrainingRequest::STATUS_APPROVED;
        $training->source = TrainingRequest::SOURCE_ADMIN_SCHEDULED;
        $training->save();

        return $training;
    }

    /** November 2026 starts on a Sunday, so column 1 is the 1st all month. */
    private function november(User $admin)
    {
        return $this->actingAs($admin)->get(route('admin.calendar', ['month' => '2026-11']));
    }

    public function test_a_one_day_training_occupies_a_single_column(): void
    {
        // Tuesday the 24th: column 3 of that week.
        $this->training(['preferred_date' => '2026-11-24', 'training_title' => 'One Day Course']);

        $this->november($this->regionalAdmin())
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('grid-column: 3 / span 1', false);
    }

    public function test_a_multi_day_training_is_one_bar_spanning_its_days(): void
    {
        // Monday to Friday — one bar four columns wide, not five chips.
        $this->training([
            'preferred_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'training_title' => 'Five Day Course',
        ]);

        $this->november($this->regionalAdmin())
            ->assertOk()
            ->assertSee('grid-column: 2 / span 5', false)
            ->assertSeeText('Five Day Course');
    }

    public function test_a_run_crossing_a_week_boundary_is_cut_into_two_bars(): void
    {
        // Thursday the 12th to Wednesday the 18th: Thu–Sat in one week,
        // Sun–Wed in the next. A single bar can't straddle two grid rows.
        $this->training([
            'preferred_date' => '2026-11-12',
            'end_date' => '2026-11-18',
            'training_title' => 'Crosses The Weekend',
        ]);

        $response = $this->november($this->regionalAdmin())->assertOk();

        $response->assertSee('grid-column: 5 / span 3', false);
        $response->assertSee('grid-column: 1 / span 4', false);
    }

    public function test_a_training_running_in_from_the_previous_month_still_shows(): void
    {
        // Ends on the 2nd, started in October. Filtering on start date alone
        // would drop it from November entirely.
        $this->training([
            'preferred_date' => '2026-10-28',
            'end_date' => '2026-11-02',
            'training_title' => 'Started In October',
        ]);

        $this->november($this->regionalAdmin())
            ->assertOk()
            ->assertSeeText('Started In October');
    }

    public function test_overlapping_trainings_are_stacked_on_separate_lanes(): void
    {
        $this->training(['preferred_date' => '2026-11-02', 'end_date' => '2026-11-06', 'training_title' => 'The Long One']);
        $this->training(['preferred_date' => '2026-11-03', 'end_date' => '2026-11-04', 'training_title' => 'The Short One']);

        $response = $this->november($this->regionalAdmin())->assertOk();

        // Lane 0 is grid-row 2 (row 1 holds the day numbers), lane 1 is row 3.
        $response->assertSee('grid-row: 2', false);
        $response->assertSee('grid-row: 3', false);
    }

    public function test_back_to_back_trainings_share_a_lane(): void
    {
        // The second starts after the first ends, so there's no reason to
        // push it onto a second row.
        $this->training(['preferred_date' => '2026-11-02', 'end_date' => '2026-11-03', 'training_title' => 'First Half']);
        $this->training(['preferred_date' => '2026-11-04', 'end_date' => '2026-11-05', 'training_title' => 'Second Half']);

        $this->november($this->regionalAdmin())
            ->assertOk()
            ->assertDontSee('grid-row: 3', false);
    }

    public function test_each_category_gets_its_own_colour(): void
    {
        $this->training(['preferred_date' => '2026-11-03', 'category' => TrainingRequest::CATEGORY_TA, 'training_title' => 'TA Course']);
        $this->training(['preferred_date' => '2026-11-17', 'category' => TrainingRequest::CATEGORY_APB, 'training_title' => 'APB Course']);

        $response = $this->november($this->regionalAdmin())->assertOk();

        $response->assertSee('bg-orange-500', false);
        $response->assertSee('bg-blue-600', false);
    }

    public function test_the_grid_only_shows_the_admins_own_region(): void
    {
        $this->training(['preferred_date' => '2026-11-03', 'training_title' => 'Ours', 'region' => 'Region III']);
        $this->training(['preferred_date' => '2026-11-04', 'training_title' => 'Somebody Elses', 'region' => 'Region V']);

        $this->november($this->regionalAdmin('Region III'))
            ->assertOk()
            ->assertSeeText('Ours')
            ->assertDontSeeText('Somebody Elses');
    }

    public function test_the_month_can_be_paged_and_defaults_to_today(): void
    {
        $admin = $this->regionalAdmin();

        $this->actingAs($admin)->get(route('admin.calendar'))
            ->assertOk()
            ->assertSee(now()->format('F Y'));

        // The prev/next links carry the month, and a junk value falls back to
        // today rather than blowing up.
        $this->actingAs($admin)->get(route('admin.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee(route('admin.calendar', ['month' => '2026-10']), false)
            ->assertSee(route('admin.calendar', ['month' => '2026-12']), false);

        $this->actingAs($admin)->get(route('admin.calendar', ['month' => 'not-a-month']))
            ->assertOk()
            ->assertSee(now()->format('F Y'));
    }

    public function test_the_search_filters_the_grid_and_keeps_the_month(): void
    {
        $this->training(['preferred_date' => '2026-11-03', 'training_title' => 'Incident Command System']);
        $this->training(['preferred_date' => '2026-11-04', 'training_title' => 'Contingency Planning']);

        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.calendar', ['month' => '2026-11', 'search' => 'Contingency']))
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSeeText('Contingency Planning')
            ->assertDontSeeText('Incident Command System');
    }

    public function test_a_super_admin_still_gets_the_all_region_agenda(): void
    {
        $this->training(['preferred_date' => '2026-11-03', 'training_title' => 'Incident Command System']);

        // With no region picked, Central's calendar spans every region, which
        // is a different reading problem and stays a list.
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.calendar'))
            ->assertOk()
            ->assertSee('All Regions')
            ->assertDontSee('grid-template-rows', false);
    }

    public function test_a_super_admin_who_picks_a_region_gets_that_regions_grid(): void
    {
        $this->training(['preferred_date' => '2026-11-02', 'end_date' => '2026-11-06', 'training_title' => 'Ours', 'region' => 'Region III']);
        $this->training(['preferred_date' => '2026-11-03', 'training_title' => 'Somebody Elses', 'region' => 'Region V']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.calendar', ['region' => 'Region III', 'month' => '2026-11']))
            ->assertOk()
            ->assertSee('grid-column: 2 / span 5', false)
            ->assertSeeText('Ours')
            ->assertDontSeeText('Somebody Elses');
    }

    public function test_central_gets_a_way_back_out_but_no_schedule_button(): void
    {
        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.calendar', ['region' => 'Region III', 'month' => '2026-11']))
            ->assertOk();

        // Scheduling stays the Regional Admin's, so Central's grid offers the
        // region picker instead of the Schedule button.
        $response->assertSee('All Regions (agenda)');
        $response->assertDontSee(route('admin.trainings.create'), false);
    }

    public function test_a_regional_admin_keeps_the_schedule_button_and_gets_no_region_picker(): void
    {
        $response = $this->actingAs($this->regionalAdmin())
            ->get(route('admin.calendar', ['month' => '2026-11']))
            ->assertOk();

        $response->assertSee(route('admin.trainings.create'), false);
        $response->assertDontSee('All Regions (agenda)');
    }

    public function test_an_unknown_region_falls_back_to_the_agenda(): void
    {
        // A hand-typed region would otherwise render an empty grid that looks
        // like a region with nothing scheduled.
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.calendar', ['region' => 'Region XYZ']))
            ->assertOk()
            ->assertDontSee('grid-template-rows', false);
    }

    public function test_the_region_is_carried_by_the_month_arrows(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.calendar', ['region' => 'Region III', 'month' => '2026-11']))
            ->assertOk()
            // Escaped, not raw: the link is an HTML attribute, so its
            // parameter separator is &amp;.
            ->assertSee(route('admin.calendar', ['month' => '2026-10', 'region' => 'Region III']));
    }

    /**
     * Paging back a year through the arrows is twenty-odd clicks, so the
     * month and year are also offered as dropdowns.
     */
    public function test_any_month_can_be_jumped_to_directly(): void
    {
        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.calendar', ['month' => '2026-11']))
            ->assertOk()
            ->assertSee('<option value="01"', false)
            ->assertSee('<option value="12"', false)
            // November is the open month, so it is the selected one.
            ->assertSee('<option value="11" selected>', false);
    }

    public function test_the_year_list_reaches_back_to_the_regions_oldest_training(): void
    {
        $this->training(['preferred_date' => '2024-03-04', 'training_title' => 'Ancient History']);

        $response = $this->actingAs($this->regionalAdmin())
            ->get(route('admin.calendar', ['month' => '2026-11']))
            ->assertOk();

        // Every year from the oldest training to today, so nothing on file is
        // more than two clicks away.
        foreach (range(2024, now()->year) as $year) {
            $response->assertSee('<option value="'.$year.'"', false);
        }
    }

    public function test_the_year_list_always_includes_the_month_being_viewed(): void
    {
        // Nothing on file at all: the control still can't offer a list that
        // excludes the year it is currently showing.
        $this->actingAs($this->regionalAdmin())
            ->get(route('admin.calendar', ['month' => '2031-05']))
            ->assertOk()
            ->assertSee('<option value="2031" selected>', false);
    }
}
