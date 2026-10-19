<?php

namespace Database\Seeders;

use App\Models\TnaForm;
use App\Models\TnaQuestion;
use App\Models\TrainingNeedsAssessment;
use Illuminate\Database\Seeder;

/**
 * Moves the TNA question content that used to live in config files (and, for
 * the fallback form, in the participant view itself) into the tna_* tables, so
 * the Super Admin can edit it.
 *
 * Nothing here is invented: every prompt and option is the wording already in
 * the app, because the typed forms are digitizations of CDTI's own instruments.
 * Safe to re-run — a form that already exists is left alone, so an admin's
 * edits are never overwritten by a reseed.
 */
class TnaFormSeeder extends Seeder
{
    public function run(): void
    {
        $this->fallbackForm();
        $this->academeForm();
        $this->nrdrrmcForm();
        $this->lguForm();
        $this->volunteerForm();
    }

    /**
     * The form shown to participant types CDTI has not mapped yet. These four
     * questions are the placeholder set that used to be hardcoded at the top of
     * participant/training-needs-assessment/index.blade.php.
     */
    private function fallbackForm(): void
    {
        $form = $this->form(TrainingNeedsAssessment::TYPE_GENERIC, 'General Assessment', null,
            'Used when a participant type has no assessment of its own. Short, and its options carry the scoring that picks a recommended training.');

        if (! $form) {
            return;
        }

        $this->question($form, 'What is your primary role or responsibility?', [
            ['I respond directly during emergencies (rescue, relief operations)', 'Emergency Response', 2],
            ['I lead or coordinate at the barangay/community level', 'Community Resilience', 2],
            ['I handle planning, policy, or DRRM programs for my LGU/agency', 'DRRM Core', 2],
            ["I support survivors' well-being (health, counseling, welfare)", 'Health & Welfare', 2],
        ]);

        $this->question($form, 'How would you describe your current knowledge of disaster risk reduction?', [
            ['New to this — just starting out', 'DRRM Core', 2],
            ['I have some experience already', null, 0],
            ["I'm experienced and want more specialized skills", 'Emergency Response', 1],
        ]);

        $this->question($form, 'What do you want your team to be ready for?', [
            ['Responding quickly when disaster strikes', 'Emergency Response', 2],
            ['Helping our community prepare and organize', 'Community Resilience', 2],
            ["Supporting people's well-being after a disaster", 'Health & Welfare', 2],
            ['Planning and managing our disaster programs', 'DRRM Core', 2],
        ]);

        $this->question($form, 'How many training hours can your team realistically commit to?', [
            ['Less than 8 hours', null, null, 8],
            ['8 to 16 hours', null, null, 16],
            ['16 to 24 hours', null, null, 24],
            ['More than 24 hours', null, null, 999],
        ]);
    }

    private function academeForm(): void
    {
        $form = $this->form(TrainingNeedsAssessment::TYPE_ACADEME, 'Academe', 'Academe',
            'OCD Training Needs Assessment tool for the Academe.');

        if (! $form) {
            return;
        }

        $this->profileQuestions($form);
        $this->responsibilitiesQuestion($form);

        foreach (config('tna_academe.facets') as $facet) {
            $this->competencyQuestion($form, $facet, 'Competency Self-Assessment');
        }

        foreach (config('tna_academe.open_questions') as $question) {
            $this->openQuestion($form, $question);
        }
    }

    private function nrdrrmcForm(): void
    {
        $form = $this->form(TrainingNeedsAssessment::TYPE_NRDRRMC, 'N&RDRRMC', 'N&RDRRMC',
            'OCD Training Needs Assessment tool for National and Regional DRRM Council members.');

        if (! $form) {
            return;
        }

        $this->profileQuestions($form);
        $this->responsibilitiesQuestion($form);

        // This tool groups its facets by DRRM domain — the domain becomes the
        // section heading the questions sit under.
        foreach (config('tna_nrdrrmc.facets') as $group) {
            foreach ($group['items'] as $facet) {
                $this->competencyQuestion($form, $facet, $group['domain']);
            }
        }

        foreach (config('tna_nrdrrmc.open_questions') as $question) {
            $this->openQuestion($form, $question);
        }
    }

    private function lguForm(): void
    {
        $form = $this->form(TrainingNeedsAssessment::TYPE_LGU, 'LGU', 'LGU',
            'OCD capability assessment for Local Government Units — hazards, organized teams, equipment and operational systems.');

        if (! $form) {
            return;
        }

        $this->profileQuestions($form);

        $this->question($form, 'What is the level of your LGU?',
            collect(config('tna_lgu.lgu_levels'))->map(fn ($level) => [$level])->all(),
            'LGU Profile');

        $this->question($form, 'Which hazards does your area face?',
            collect(config('tna_lgu.hazards'))->map(fn ($hazard) => [$hazard])->all(),
            'Hazards', TnaQuestion::KIND_MULTI_CHOICE);

        $this->question($form, 'How would you rank the hazards in your area?',
            collect(config('tna_lgu.hazard_ranks'))->map(fn ($rank) => [$rank])->all(),
            'Hazards');

        $this->question($form, 'What is the risk level of your area?',
            collect(config('tna_lgu.risk_levels'))->map(fn ($level, $name) => [$name.' — '.$level['description']])->values()->all(),
            'Hazards');

        foreach (config('tna_lgu.organized_team_disciplines') as $discipline) {
            $this->question($form, "How many teams are organized for {$discipline}?",
                collect(config('tna_lgu.team_sizes'))->map(fn ($size) => [$size])->all(),
                'Organized Teams');
        }

        foreach (config('tna_lgu.capability_disciplines') as $discipline) {
            $this->question($form, "What is your personnel competency level for {$discipline}?",
                collect(config('tna_lgu.personnel_competency_levels'))->map(fn ($level) => [$level])->all(),
                'Capability');

            $this->question($form, "What is your team capability level for {$discipline}?",
                collect(config('tna_lgu.team_capability_levels'))->map(fn ($level) => [$level])->all(),
                'Capability');
        }

        foreach (config('tna_lgu.equipment') as $group) {
            $this->question($form, "Which {$group['label']} do you have on hand?",
                collect($group['items'])->map(fn ($item) => [$item])->all(),
                'Equipment', TnaQuestion::KIND_MULTI_CHOICE);
        }

        $this->question($form, 'Which operational systems are in place?',
            collect(config('tna_lgu.operational_systems'))->map(fn ($system) => [$system])->all(),
            'Operational Systems', TnaQuestion::KIND_MULTI_CHOICE);

        $this->question($form, 'Which sustainment arrangements are in place?',
            collect(config('tna_lgu.sustainment_items'))->map(fn ($item) => [$item])->all(),
            'Sustainment', TnaQuestion::KIND_MULTI_CHOICE);
    }

    private function volunteerForm(): void
    {
        $form = $this->form(TrainingNeedsAssessment::TYPE_VOLUNTEER, 'Volunteer', 'Volunteer',
            'OCD Volunteers Baseline Data — organization profile, specializations, trainings taken and mobilization readiness.');

        if (! $form) {
            return;
        }

        $this->question($form, 'Name of volunteer organization', [], 'Organization Profile', TnaQuestion::KIND_SHORT_TEXT);

        $this->question($form, 'Which region is your organization based in?',
            collect(config('tna_volunteer.regions'))->map(fn ($region) => [$region])->all(),
            'Organization Profile');

        $this->question($form, 'What level does your organization operate at?',
            collect(config('tna_volunteer.levels'))->map(fn ($level) => [$level])->all(),
            'Organization Profile');

        $this->question($form, 'What is your accreditation status?',
            collect(config('tna_volunteer.accreditation_statuses'))->map(fn ($status) => [$status])->all(),
            'Organization Profile');

        foreach (config('tna_volunteer.specializations') as $specialization) {
            $this->question($form, "Number of organized teams/units for {$specialization}", [],
                'Specializations', TnaQuestion::KIND_NUMBER);
        }

        $this->question($form, 'Which trainings have your volunteers taken?',
            collect(config('tna_volunteer.trainings'))->map(fn ($training) => [$training])->all(),
            'Trainings', TnaQuestion::KIND_MULTI_CHOICE);

        $this->question($form, 'How quickly can your organization be mobilized?',
            collect(config('tna_volunteer.mobilization_readiness'))->map(fn ($readiness) => [$readiness])->all(),
            'Mobilization');
    }

    /**
     * Creates the form, or returns null when one with this key already exists
     * so a reseed never overwrites edits made in the admin editor.
     */
    private function form(string $key, string $label, ?string $participantType, string $description): ?TnaForm
    {
        if (TnaForm::where('key', $key)->exists()) {
            return null;
        }

        return TnaForm::create([
            'key' => $key,
            'label' => $label,
            'participant_type' => $participantType,
            'description' => $description,
            'is_active' => true,
        ]);
    }

    /** Office/institution and position, asked by every official tool. */
    private function profileQuestions(TnaForm $form): void
    {
        $this->question($form, 'Office / Institution', [], 'Respondent', TnaQuestion::KIND_SHORT_TEXT);
        $this->question($form, 'Position / Title', [], 'Respondent', TnaQuestion::KIND_SHORT_TEXT);
    }

    /** The 30-item checklist that is word-for-word identical across the tools. */
    private function responsibilitiesQuestion(TnaForm $form): void
    {
        $this->question($form, 'Which of these describe your current role and responsibilities?',
            collect(config('tna_common.responsibilities'))->map(fn ($item) => [$item])->all(),
            'Current Role and Responsibilities', TnaQuestion::KIND_MULTI_CHOICE);
    }

    private function competencyQuestion(TnaForm $form, string $facet, string $groupLabel): void
    {
        $this->question($form, $facet, [], $groupLabel, TnaQuestion::KIND_COMPETENCY,
            'Competency: 1 = Basic … 5 = Excellent. Importance: 1 = Not Important … 5 = Very Important.');
    }

    private function openQuestion(TnaForm $form, string $prompt): void
    {
        $this->question($form, $prompt, [], 'Open Questions', TnaQuestion::KIND_SHORT_TEXT, null, false);
    }

    /**
     * @param  array<int, array{0: string, 1?: ?string, 2?: ?int, 3?: ?int}>  $options
     *                                                                                 label, category, points, hours
     */
    private function question(
        TnaForm $form,
        string $prompt,
        array $options = [],
        ?string $groupLabel = null,
        string $kind = TnaQuestion::KIND_SINGLE_CHOICE,
        ?string $helpText = null,
        bool $required = true,
    ): void {
        $question = $form->questions()->create([
            'position' => $form->questions()->max('position') + 1,
            'group_label' => $groupLabel,
            'prompt' => $prompt,
            'help_text' => $helpText,
            'kind' => $kind,
            'is_required' => $required,
        ]);

        foreach ($options as $index => $option) {
            $question->options()->create([
                'position' => $index + 1,
                'label' => $option[0],
                'category' => $option[1] ?? null,
                'points' => $option[2] ?? null,
                'hours' => $option[3] ?? null,
            ]);
        }
    }
}
