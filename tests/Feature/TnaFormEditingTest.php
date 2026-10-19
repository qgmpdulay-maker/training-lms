<?php

namespace Tests\Feature;

use App\Models\TnaForm;
use App\Models\TnaOption;
use App\Models\TnaQuestion;
use App\Models\User;
use Database\Seeders\TnaFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The TNA questionnaires are editable by the Super Admin (decided 2026-10-19):
 * the questions moved out of config files into the tna_* tables, and the
 * assessment a participant fills in is rendered from those rows.
 */
class TnaFormEditingTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): TnaForm
    {
        return TnaForm::create(array_merge([
            'key' => 'ocd-personnel',
            'label' => 'OCD Personnel',
            'participant_type' => 'OCD PERSONNEL',
            'description' => 'For OCD staff.',
            'is_active' => true,
        ], $overrides));
    }

    public function test_the_seeder_imports_every_shipped_questionnaire(): void
    {
        $this->seed(TnaFormSeeder::class);

        // One per participant type the app already served, plus the fallback.
        $this->assertEqualsCanonicalizing(
            ['generic', 'academe', 'nrdrrmc', 'lgu', 'volunteer'],
            TnaForm::pluck('key')->all(),
        );
        $this->assertNull(TnaForm::where('key', 'generic')->value('participant_type'));
        $this->assertSame(4, TnaForm::where('key', 'generic')->first()->questions()->count());

        // The content is the app's own wording, not something invented here.
        $this->assertDatabaseHas('tna_questions', ['prompt' => 'What is your primary role or responsibility?']);
        $this->assertDatabaseHas('tna_options', ['label' => 'Less than 8 hours', 'hours' => 8]);

        // Re-running leaves an admin's edits alone.
        TnaForm::where('key', 'generic')->first()->questions()->first()->update(['prompt' => 'Edited prompt']);
        $this->seed(TnaFormSeeder::class);
        $this->assertDatabaseHas('tna_questions', ['prompt' => 'Edited prompt']);
        $this->assertSame(5, TnaForm::count());
    }

    public function test_a_super_admin_adds_a_question_with_scored_options(): void
    {
        $form = $this->form();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.tna-forms.questions.store', $form), [
                'prompt' => 'Which operations do you support?',
                'kind' => TnaQuestion::KIND_MULTI_CHOICE,
                'group_label' => 'Role',
                'is_required' => '1',
                'options' => [
                    ['label' => 'Search and rescue', 'category' => 'Emergency Response', 'points' => '2', 'hours' => ''],
                    ['label' => 'Camp management', 'category' => 'Health & Welfare', 'points' => '1', 'hours' => '16'],
                    // A blank label is how a row is dropped.
                    ['label' => '', 'category' => '', 'points' => '', 'hours' => ''],
                ],
            ])
            ->assertRedirect(route('admin.tna-forms.edit', $form));

        $question = $form->questions()->sole();
        $this->assertSame('Which operations do you support?', $question->prompt);
        $this->assertSame(TnaQuestion::KIND_MULTI_CHOICE, $question->kind);
        $this->assertSame(2, $question->options()->count());
        $this->assertSame('Emergency Response', $question->options()->first()->category);
        $this->assertSame(16, $question->options()->where('label', 'Camp management')->sole()->hours);
    }

    public function test_questions_can_be_edited_reordered_and_removed(): void
    {
        $form = $this->form();
        $first = $form->questions()->create(['position' => 1, 'prompt' => 'First', 'kind' => TnaQuestion::KIND_SINGLE_CHOICE]);
        $second = $form->questions()->create(['position' => 2, 'prompt' => 'Second', 'kind' => TnaQuestion::KIND_SINGLE_CHOICE]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.tna-questions.update', $first), [
                'prompt' => 'First, reworded',
                'kind' => TnaQuestion::KIND_SHORT_TEXT,
                'is_required' => '0',
            ])
            ->assertRedirect(route('admin.tna-forms.edit', $form->id));

        $first->refresh();
        $this->assertSame('First, reworded', $first->prompt);
        $this->assertFalse($first->is_required);

        $this->actingAs($superAdmin)
            ->post(route('admin.tna-questions.move', $second), ['direction' => 'up']);

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);

        $this->actingAs($superAdmin)->delete(route('admin.tna-questions.destroy', $second));
        $this->assertDatabaseMissing('tna_questions', ['id' => $second->id]);
    }

    public function test_deleting_a_question_takes_its_options_with_it(): void
    {
        $form = $this->form();
        $question = $form->questions()->create(['position' => 1, 'prompt' => 'Pick one', 'kind' => TnaQuestion::KIND_SINGLE_CHOICE]);
        $question->options()->create(['position' => 1, 'label' => 'An option']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('admin.tna-questions.destroy', $question));

        $this->assertSame(0, TnaOption::count());
    }

    public function test_a_new_type_is_served_to_its_participants_straight_away(): void
    {
        $this->seed(TnaFormSeeder::class);

        $form = $this->form();
        $question = $form->questions()->create(['position' => 1, 'prompt' => 'Which office are you in?', 'kind' => TnaQuestion::KIND_SINGLE_CHOICE]);
        $question->options()->create(['position' => 1, 'label' => 'Regional Office', 'category' => 'DRRM Core', 'points' => 2]);

        $participant = User::factory()->create(['participant_type' => 'OCD PERSONNEL']);

        $this->actingAs($participant)
            ->get(route('training-needs-assessment.index'))
            ->assertOk()
            ->assertSee('OCD Personnel')
            ->assertSee('Which office are you in?')
            ->assertSee('Regional Office')
            // Not the fallback assessment.
            ->assertDontSee('What is your primary role or responsibility?');
    }

    public function test_an_unmapped_type_gets_the_fallback_assessment(): void
    {
        $this->seed(TnaFormSeeder::class);

        $participant = User::factory()->create(['participant_type' => 'Private Sector']);

        $this->actingAs($participant)
            ->get(route('training-needs-assessment.index'))
            ->assertOk()
            ->assertSee('What is your primary role or responsibility?');
    }

    public function test_a_draft_assessment_is_not_served_to_participants(): void
    {
        $this->seed(TnaFormSeeder::class);

        $form = $this->form(['is_active' => false]);
        $form->questions()->create(['position' => 1, 'prompt' => 'Draft question', 'kind' => TnaQuestion::KIND_SHORT_TEXT]);

        $this->actingAs(User::factory()->create(['participant_type' => 'OCD PERSONNEL']))
            ->get(route('training-needs-assessment.index'))
            ->assertOk()
            ->assertDontSee('Draft question')
            // Falls back rather than showing nothing.
            ->assertSee('What is your primary role or responsibility?');
    }

    public function test_only_a_super_admin_can_edit_the_questionnaires(): void
    {
        $form = $this->form();
        $question = $form->questions()->create(['position' => 1, 'prompt' => 'A question', 'kind' => TnaQuestion::KIND_SHORT_TEXT]);

        foreach ([User::factory()->admin('Region III')->create(), User::factory()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.tna-forms.index'))->assertForbidden();
            $this->actingAs($user)->put(route('admin.tna-questions.update', $question), [
                'prompt' => 'Changed', 'kind' => TnaQuestion::KIND_SHORT_TEXT,
            ])->assertForbidden();
        }

        $this->assertSame('A question', $question->fresh()->prompt);
    }
}
