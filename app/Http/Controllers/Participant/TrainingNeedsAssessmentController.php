<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\TrainingNeedsAssessment;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TrainingNeedsAssessmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->participant_type === 'Academe') {
            return $this->academeIndex();
        }

        if ($user->participant_type === 'N&RDRRMC') {
            return $this->nrdrrmcIndex();
        }

        if ($user->participant_type === 'LGU') {
            return $this->lguIndex();
        }

        if ($user->participant_type === 'Volunteers') {
            return $this->volunteerIndex();
        }

        $trainings = config('trainings.catalog');

        $existingRecommendation = $user->recommended_training_slug
            ? collect($trainings)->firstWhere('slug', $user->recommended_training_slug)
            : null;

        return view('participant.training-needs-assessment.index', compact('trainings', 'existingRecommendation'));
    }

    public function storeRecommendation(Request $request)
    {
        $catalog = collect(config('trainings.catalog'));

        $validated = $this->validateOrFail($request, [
            'training_slug' => ['required', 'string', 'in:'.$catalog->pluck('slug')->implode(',')],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question' => ['required', 'string'],
            'answers.*.selected' => ['required', 'string'],
            'answers.*.category' => ['nullable', 'string'],
            'answers.*.points' => ['nullable', 'integer'],
            'answers.*.hours' => ['nullable', 'integer'],
            'category_scores' => ['required', 'array'],
            'category_scores.*' => ['integer'],
            'top_category' => ['nullable', 'string'],
            'max_hours' => ['nullable', 'integer'],
        ]);

        $training = $catalog->firstWhere('slug', $validated['training_slug']);
        $user = Auth::user();

        $user->trainingNeedsAssessments()->create([
            'type' => TrainingNeedsAssessment::TYPE_GENERIC,
            'answers' => $validated['answers'],
            'category_scores' => $validated['category_scores'],
            'top_category' => $validated['top_category'] ?? null,
            'max_hours' => $validated['max_hours'] ?? null,
            'recommended_training_slug' => $training['slug'],
            'recommended_training_title' => $training['title'],
            'recommended_training_category' => $training['category'] ?? null,
        ]);

        $user->forceFill([
            'recommended_training_slug' => $validated['training_slug'],
            'recommended_training_at' => now(),
        ])->save();

        return response()->noContent();
    }

    protected function academeIndex()
    {
        $existing = $this->latestSubmission(TrainingNeedsAssessment::TYPE_ACADEME);

        return view('participant.training-needs-assessment.academe', [
            'responsibilities' => config('tna_common.responsibilities'),
            'facets' => config('tna_academe.facets'),
            'openQuestions' => config('tna_academe.open_questions'),
            'existing' => $existing,
        ]);
    }

    public function storeAcademe(Request $request)
    {
        $responsibilities = config('tna_common.responsibilities');
        $facets = config('tna_academe.facets');
        $openQuestions = config('tna_academe.open_questions');

        $validated = $this->validateOrFail($request, [
            'institution' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'responsibilities' => ['array'],
            'responsibilities.*' => [Rule::in($responsibilities)],
            'competencies' => ['required', 'array', 'size:'.count($facets)],
            'competencies.*.facet' => ['required', Rule::in($facets)],
            'competencies.*.competency' => ['required', 'integer', 'between:1,5'],
            'competencies.*.importance' => ['required', 'integer', 'between:1,5'],
            'open_responses' => ['required', 'array', 'size:'.count($openQuestions)],
            'open_responses.*.question' => ['required', Rule::in($openQuestions)],
            'open_responses.*.answer' => ['nullable', 'string', 'max:2000'],
        ]);

        $competencies = $this->scoreCompetencies($validated['competencies']);

        $this->storeStructuredAssessment(TrainingNeedsAssessment::TYPE_ACADEME, $validated, $competencies);

        return response()->json(['competencies' => $competencies->all()]);
    }

    protected function nrdrrmcIndex()
    {
        $facetGroups = $this->indexedFacetGroups(config('tna_nrdrrmc.facets'));
        $existing = $this->latestSubmission(TrainingNeedsAssessment::TYPE_NRDRRMC);

        return view('participant.training-needs-assessment.nrdrrmc', [
            'responsibilities' => config('tna_common.responsibilities'),
            'facetGroups' => $facetGroups,
            'openQuestions' => config('tna_nrdrrmc.open_questions'),
            'existing' => $existing,
        ]);
    }

    public function storeNrdrrmc(Request $request)
    {
        $responsibilities = config('tna_common.responsibilities');
        $facetLabels = $this->flattenFacetLabels(config('tna_nrdrrmc.facets'));
        $openQuestions = config('tna_nrdrrmc.open_questions');

        $validated = $this->validateOrFail($request, [
            'institution' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'responsibilities' => ['array'],
            'responsibilities.*' => [Rule::in($responsibilities)],
            'competencies' => ['required', 'array', 'size:'.count($facetLabels)],
            'competencies.*.facet' => ['required', Rule::in($facetLabels)],
            'competencies.*.competency' => ['required', 'integer', 'between:1,5'],
            'competencies.*.importance' => ['required', 'integer', 'between:1,5'],
            'open_responses' => ['required', 'array', 'size:'.count($openQuestions)],
            'open_responses.*.question' => ['required', Rule::in($openQuestions)],
            'open_responses.*.answer' => ['nullable', 'string', 'max:2000'],
        ]);

        $competencies = $this->scoreCompetencies($validated['competencies']);

        $this->storeStructuredAssessment(TrainingNeedsAssessment::TYPE_NRDRRMC, $validated, $competencies);

        return response()->json(['competencies' => $competencies->all()]);
    }

    protected function lguIndex()
    {
        $existing = $this->latestSubmission(TrainingNeedsAssessment::TYPE_LGU);

        return view('participant.training-needs-assessment.lgu', [
            'lguLevels' => config('tna_lgu.lgu_levels'),
            'regions' => config('regions.list'),
            'hazards' => config('tna_lgu.hazards'),
            'hazardRanks' => config('tna_lgu.hazard_ranks'),
            'riskLevels' => config('tna_lgu.risk_levels'),
            'organizedTeamDisciplines' => config('tna_lgu.organized_team_disciplines'),
            'capabilityDisciplines' => config('tna_lgu.capability_disciplines'),
            'teamSizes' => config('tna_lgu.team_sizes'),
            'personnelCompetencyLevels' => config('tna_lgu.personnel_competency_levels'),
            'teamCapabilityLevels' => config('tna_lgu.team_capability_levels'),
            'equipment' => config('tna_lgu.equipment'),
            'operationalSystems' => config('tna_lgu.operational_systems'),
            'sustainmentItems' => config('tna_lgu.sustainment_items'),
            'existing' => $existing,
        ]);
    }

    public function storeLgu(Request $request)
    {
        $lguLevels = config('tna_lgu.lgu_levels');
        $regions = config('regions.list');
        $hazards = config('tna_lgu.hazards');
        $hazardRanks = config('tna_lgu.hazard_ranks');
        $riskLevels = array_keys(config('tna_lgu.risk_levels'));
        $organizedTeamDisciplines = config('tna_lgu.organized_team_disciplines');
        $capabilityDisciplines = config('tna_lgu.capability_disciplines');
        $teamSizes = config('tna_lgu.team_sizes');
        $personnelLevels = config('tna_lgu.personnel_competency_levels');
        $teamCapLevels = config('tna_lgu.team_capability_levels');
        $equipment = config('tna_lgu.equipment');
        $operationalSystems = config('tna_lgu.operational_systems');
        $sustainmentItems = config('tna_lgu.sustainment_items');

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'region' => ['required', Rule::in($regions)],
            'lgu_level' => ['required', Rule::in($lguLevels)],
            'lgu_name' => ['required', 'string', 'max:255'],
            'population' => ['required', 'integer', 'min:1'],

            'hazard_ranks' => ['required', 'array', 'size:'.count($hazards)],
            'hazard_ranks.*.hazard' => ['required', Rule::in($hazards)],
            'hazard_ranks.*.rank' => ['required', Rule::in($hazardRanks)],

            'average_risk_level' => ['required', Rule::in($riskLevels)],

            'organized_teams' => ['required', 'array', 'size:'.count($organizedTeamDisciplines)],
            'organized_teams.*.discipline' => ['required', Rule::in($organizedTeamDisciplines)],
            'organized_teams.*.size' => ['required', Rule::in($teamSizes)],

            'personnel_competency' => ['required', 'array', 'size:'.count($capabilityDisciplines)],
            'personnel_competency.*.discipline' => ['required', Rule::in($capabilityDisciplines)],
            'personnel_competency.*.level' => ['required', Rule::in($personnelLevels)],

            'total_responders_basic' => ['required', 'integer', 'min:0'],
            'deployable_responders_technician' => ['required', 'integer', 'min:0'],
            'team_assigned_lead_responders' => ['required', 'integer', 'min:0'],

            'team_fact_sheet_files' => ['required', 'array', 'min:1', 'max:5'],
            'team_fact_sheet_files.*' => ['file', 'mimes:pdf,xls,xlsx,csv', 'max:10240'],

            'team_capability' => ['required', 'array', 'size:'.count($capabilityDisciplines)],
            'team_capability.*.discipline' => ['required', Rule::in($capabilityDisciplines)],
            'team_capability.*.level' => ['required', Rule::in($teamCapLevels)],

            'srr_teams_count' => ['required', 'string', 'max:2000'],
            'advanced_teams_count' => ['required', 'string', 'max:2000'],
            'trainings_needed_response_teams' => ['required', 'string', 'max:2000'],
            'trainings_undertaken' => ['required', 'string', 'max:2000'],
            'trainings_needed_imt' => ['required', 'string', 'max:2000'],
            'trainings_needed_eoc' => ['required', 'string', 'max:2000'],
            'other_teams_organized' => ['required', 'string', 'max:2000'],
            'trainings_needed_other_teams' => ['required', 'string', 'max:2000'],

            'operational_systems' => ['required', 'array', 'min:1'],
            'operational_systems.*' => [Rule::in($operationalSystems)],
            'other_operational_systems' => ['required', 'string', 'max:2000'],
            'simulation_exercises' => ['required', 'string', 'max:2000'],
            'trainings_needed_sops' => ['required', 'string', 'max:2000'],

            'sustainment_checklist' => ['required', 'array', 'min:1'],
            'sustainment_checklist.*' => [Rule::in($sustainmentItems)],
            'other_sustainment_documents' => ['required', 'string', 'max:2000'],
            'trainings_needed_sustainment' => ['required', 'string', 'max:2000'],
        ];

        foreach ($equipment as $key => $group) {
            $rules[$key] = ['required', 'array', 'min:1'];
            $rules[$key.'.*'] = [Rule::in($group['items'])];
            $rules['other_'.$key] = ['required', 'string', 'max:1000'];
        }

        $validated = $this->validateOrFail($request, $rules);

        $ranksUsed = collect($validated['hazard_ranks'])->pluck('rank')->filter(fn ($rank) => $rank !== 'NONE');
        if ($ranksUsed->duplicates()->isNotEmpty()) {
            return response()->json([
                'message' => 'Each of Rank #1, Rank #2, and Rank #3 can only be assigned to one hazard.',
                'errors' => ['hazard_ranks' => ['Each of Rank #1, Rank #2, and Rank #3 can only be assigned to one hazard.']],
            ], 422);
        }

        $riskLevelConfig = config('tna_lgu.risk_levels')[$validated['average_risk_level']];
        $baselineResponders = (int) ceil($validated['population'] / $riskLevelConfig['ratio_per_responder']);

        $filePaths = collect($request->file('team_fact_sheet_files'))
            ->map(fn ($file) => $file->store('tna/lgu/team-fact-sheets', 'public'))
            ->all();

        $equipmentAnswers = [];
        foreach ($equipment as $key => $group) {
            $equipmentAnswers[$key] = [
                'label' => $group['label'],
                'checked' => $validated[$key],
                'other' => $validated['other_'.$key],
            ];
        }

        $user = Auth::user();

        $user->trainingNeedsAssessments()->create([
            'type' => TrainingNeedsAssessment::TYPE_LGU,
            'answers' => [
                'profile' => [
                    'full_name' => $validated['full_name'],
                    'position' => $validated['position'],
                    'region' => $validated['region'],
                    'lgu_level' => $validated['lgu_level'],
                    'lgu_name' => $validated['lgu_name'],
                    'population' => $validated['population'],
                ],
                'hazard_ranks' => $validated['hazard_ranks'],
                'organization' => [
                    'average_risk_level' => $validated['average_risk_level'],
                    'baseline_responders' => $baselineResponders,
                    'organized_teams' => $validated['organized_teams'],
                    'personnel_competency' => $validated['personnel_competency'],
                    'total_responders_basic' => $validated['total_responders_basic'],
                    'deployable_responders_technician' => $validated['deployable_responders_technician'],
                    'team_assigned_lead_responders' => $validated['team_assigned_lead_responders'],
                    'team_fact_sheet_files' => $filePaths,
                ],
                'team_capability' => [
                    'ratings' => $validated['team_capability'],
                    'srr_teams_count' => $validated['srr_teams_count'],
                    'advanced_teams_count' => $validated['advanced_teams_count'],
                    'trainings_needed_response_teams' => $validated['trainings_needed_response_teams'],
                    'trainings_undertaken' => $validated['trainings_undertaken'],
                    'trainings_needed_imt' => $validated['trainings_needed_imt'],
                    'trainings_needed_eoc' => $validated['trainings_needed_eoc'],
                    'other_teams_organized' => $validated['other_teams_organized'],
                    'trainings_needed_other_teams' => $validated['trainings_needed_other_teams'],
                ],
                'equipment' => $equipmentAnswers,
                'operational_systems' => [
                    'checked' => $validated['operational_systems'],
                    'other' => $validated['other_operational_systems'],
                    'simulation_exercises' => $validated['simulation_exercises'],
                    'trainings_needed' => $validated['trainings_needed_sops'],
                ],
                'sustainment' => [
                    'checked' => $validated['sustainment_checklist'],
                    'other' => $validated['other_sustainment_documents'],
                    'trainings_needed' => $validated['trainings_needed_sustainment'],
                ],
            ],
            'category_scores' => ['Baseline Responders' => $baselineResponders],
            'top_category' => $validated['lgu_name'].' — '.$validated['average_risk_level'],
            'max_hours' => null,
            'recommended_training_slug' => null,
            'recommended_training_title' => null,
            'recommended_training_category' => $validated['average_risk_level'],
        ]);

        return response()->json(['baseline_responders' => $baselineResponders]);
    }

    protected function volunteerIndex()
    {
        $existing = $this->latestSubmission(TrainingNeedsAssessment::TYPE_VOLUNTEER);

        return view('participant.training-needs-assessment.volunteer', [
            'regions' => config('tna_volunteer.regions'),
            'levels' => config('tna_volunteer.levels'),
            'accreditationStatuses' => config('tna_volunteer.accreditation_statuses'),
            'specializations' => config('tna_volunteer.specializations'),
            'trainings' => config('tna_volunteer.trainings'),
            'mobilizationReadiness' => config('tna_volunteer.mobilization_readiness'),
            'existing' => $existing,
        ]);
    }

    public function storeVolunteer(Request $request)
    {
        $regions = config('tna_volunteer.regions');
        $levels = config('tna_volunteer.levels');
        $accreditationStatuses = config('tna_volunteer.accreditation_statuses');
        $specializations = config('tna_volunteer.specializations');
        $trainings = config('tna_volunteer.trainings');
        $mobilizationReadiness = config('tna_volunteer.mobilization_readiness');

        $validated = $this->validateOrFail($request, [
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:255'],
            'group_name' => ['required', 'string', 'max:255'],
            'level' => ['required', Rule::in($levels)],
            'accreditation_status' => ['required', Rule::in($accreditationStatuses)],
            'accrediting_authority' => ['required', 'string', 'max:255'],
            'accreditation_date' => ['required', 'date'],
            'region' => ['required', Rule::in($regions)],

            'active_volunteers' => ['required', 'string', 'max:50'],
            'age_18_20' => ['required', 'string', 'max:50'],
            'age_21_30' => ['required', 'string', 'max:50'],
            'age_31_40' => ['required', 'string', 'max:50'],
            'age_41_50' => ['required', 'string', 'max:50'],
            'age_51_60' => ['required', 'string', 'max:50'],
            'male_count' => ['required', 'string', 'max:50'],
            'female_count' => ['nullable', 'string', 'max:50'],

            'specializations' => ['required', 'array', 'size:'.count($specializations)],
            'specializations.*' => ['required', 'string', 'max:1000'],
            'other_specializations' => ['required', 'string', 'max:2000'],
            'trainings_completed' => ['required', 'array', 'min:1'],
            'trainings_completed.*' => [Rule::in($trainings)],
            'other_trainings_completed' => ['required', 'string', 'max:2000'],

            'coordination_mechanisms' => ['required', 'string', 'max:2000'],
            'mobilization_readiness' => ['required', 'array', 'min:1'],
            'mobilization_readiness.*' => [Rule::in($mobilizationReadiness)],
            'transportation_assets_file' => ['required', 'file', 'mimes:pdf', 'max:102400'],
            'response_equipment_file' => ['required', 'file', 'mimes:pdf', 'max:102400'],

            'deployment_1' => ['required', 'string', 'max:2000'],
            'deployment_2' => ['required', 'string', 'max:2000'],
            'deployment_3' => ['required', 'string', 'max:2000'],

            'identified_gaps' => ['required', 'string', 'max:2000'],
            'priority_needs' => ['required', 'string', 'max:2000'],
            'recommendations_collaboration' => ['nullable', 'string', 'max:2000'],
        ]);

        $transportationPath = $request->file('transportation_assets_file')->store('tna/volunteer/transportation-assets', 'public');
        $equipmentPath = $request->file('response_equipment_file')->store('tna/volunteer/response-equipment', 'public');

        $specializationAnswers = [];
        foreach ($specializations as $index => $label) {
            $specializationAnswers[] = [
                'label' => $label,
                'value' => $validated['specializations'][$index],
            ];
        }

        $user = Auth::user();

        $user->trainingNeedsAssessments()->create([
            'type' => TrainingNeedsAssessment::TYPE_VOLUNTEER,
            'answers' => [
                'profile' => [
                    'full_name' => $validated['full_name'],
                    'position' => $validated['position'],
                    'contact_number' => $validated['contact_number'],
                    'group_name' => $validated['group_name'],
                    'level' => $validated['level'],
                    'accreditation_status' => $validated['accreditation_status'],
                    'accrediting_authority' => $validated['accrediting_authority'],
                    'accreditation_date' => $validated['accreditation_date'],
                    'region' => $validated['region'],
                ],
                'volunteer_profile' => [
                    'active_volunteers' => $validated['active_volunteers'],
                    'age_18_20' => $validated['age_18_20'],
                    'age_21_30' => $validated['age_21_30'],
                    'age_31_40' => $validated['age_31_40'],
                    'age_41_50' => $validated['age_41_50'],
                    'age_51_60' => $validated['age_51_60'],
                    'male_count' => $validated['male_count'],
                    'female_count' => $validated['female_count'] ?? null,
                ],
                'capacity' => [
                    'specializations' => $specializationAnswers,
                    'other_specializations' => $validated['other_specializations'],
                    'trainings_completed' => $validated['trainings_completed'],
                    'other_trainings_completed' => $validated['other_trainings_completed'],
                ],
                'mobilization' => [
                    'coordination_mechanisms' => $validated['coordination_mechanisms'],
                    'readiness' => $validated['mobilization_readiness'],
                    'transportation_assets_file' => $transportationPath,
                    'response_equipment_file' => $equipmentPath,
                ],
                'deployments' => [
                    $validated['deployment_1'],
                    $validated['deployment_2'],
                    $validated['deployment_3'],
                ],
                'gap_analysis' => [
                    'identified_gaps' => $validated['identified_gaps'],
                    'priority_needs' => $validated['priority_needs'],
                    'recommendations_collaboration' => $validated['recommendations_collaboration'] ?? null,
                ],
            ],
            'category_scores' => [],
            'top_category' => $validated['group_name'].' — '.$validated['level'],
            'max_hours' => null,
            'recommended_training_slug' => null,
            'recommended_training_title' => null,
            'recommended_training_category' => $validated['accreditation_status'],
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * The app only auto-renders validation failures as JSON for `api/*`
     * routes (see bootstrap/app.php); everywhere else `$request->validate()`
     * redirects back on failure. These endpoints are fetch()-driven from
     * Alpine components that expect a JSON 422, so validate explicitly and
     * short-circuit with our own JSON response instead.
     */
    private function validateOrFail(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new HttpResponseException(
                response()->json(['errors' => $validator->errors()], 422)
            );
        }

        return $validator->validated();
    }

    private function latestSubmission(string $type): ?TrainingNeedsAssessment
    {
        return Auth::user()->trainingNeedsAssessments()
            ->where('type', $type)
            ->latest()
            ->first();
    }

    /**
     * Flattens domain-grouped facets (as authored in config) into unique
     * "Domain — Item" labels used to identify a facet in storage/validation.
     */
    private function flattenFacetLabels(array $groups): array
    {
        $labels = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $labels[] = $group['domain'].' — '.$item;
            }
        }

        return $labels;
    }

    /**
     * Same domain grouping, but with each item carrying its flat index into
     * the (also flat) Alpine `competencies` array the view binds ratings to.
     */
    private function indexedFacetGroups(array $groups): array
    {
        $facetGroups = [];
        $index = 0;

        foreach ($groups as $group) {
            $entries = [];
            foreach ($group['items'] as $item) {
                $entries[] = [
                    'index' => $index,
                    'item' => $item,
                    'facet' => $group['domain'].' — '.$item,
                ];
                $index++;
            }
            $facetGroups[] = ['domain' => $group['domain'], 'entries' => $entries];
        }

        return $facetGroups;
    }

    /**
     * Scores each submitted competency per the TNA tool's formula: Gap Score
     * = Importance - Competence, WTP = Importance x Gap Score. Sorted so the
     * highest-priority training need is first.
     *
     * @param  array<int, array{facet: string, competency: int, importance: int}>  $entries
     */
    private function scoreCompetencies(array $entries): Collection
    {
        return collect($entries)
            ->map(function (array $entry) {
                $gapScore = $entry['importance'] - $entry['competency'];
                $wtp = $entry['importance'] * $gapScore;

                return [
                    'facet' => $entry['facet'],
                    'competency' => $entry['competency'],
                    'importance' => $entry['importance'],
                    'gap_score' => $gapScore,
                    'wtp' => $wtp,
                    'priority' => $this->priorityLabel($gapScore, $wtp),
                ];
            })
            ->sortByDesc('wtp')
            ->values();
    }

    /**
     * Priority label per the TNA tool's formula. A non-positive gap means
     * the participant already meets or exceeds what the role needs.
     */
    private function priorityLabel(int $gapScore, int $wtp): string
    {
        if ($gapScore <= 0) {
            return 'Little to No Training Needed';
        }

        return match (true) {
            $wtp >= 21 => 'Extremely High Priority',
            $wtp >= 16 => 'High Priority',
            $wtp >= 11 => 'Moderate Priority',
            $wtp >= 6 => 'Low Priority',
            default => 'Not a Priority',
        };
    }

    private function storeStructuredAssessment(string $type, array $validated, Collection $competencies): void
    {
        $topFacet = $competencies->first();

        Auth::user()->trainingNeedsAssessments()->create([
            'type' => $type,
            'answers' => [
                'profile' => [
                    'institution' => $validated['institution'],
                    'position' => $validated['position'],
                ],
                'responsibilities' => $validated['responsibilities'] ?? [],
                'competencies' => $competencies->all(),
                'open_responses' => $validated['open_responses'],
            ],
            'category_scores' => $competencies->pluck('wtp', 'facet')->all(),
            'top_category' => $topFacet['facet'] ?? null,
            'max_hours' => null,
            'recommended_training_slug' => null,
            'recommended_training_title' => null,
            'recommended_training_category' => $topFacet['priority'] ?? null,
        ]);
    }
}
