@php
    $profile = $existing->answers['profile'] ?? [];
    $org = $existing->answers['organization'] ?? [];
    $teamCapExisting = $existing->answers['team_capability'] ?? [];
    $existingEquipment = $existing->answers['equipment'] ?? [];

    $hazardRanksInit = $existing->answers['hazard_ranks'] ?? collect($hazards)->map(fn ($h) => ['hazard' => $h, 'rank' => 'NONE'])->values()->all();
    $organizedTeamsInit = $org['organized_teams'] ?? collect($organizedTeamDisciplines)->map(fn ($d) => ['discipline' => $d, 'size' => 'N/A'])->values()->all();
    $personnelCompetencyInit = $org['personnel_competency'] ?? collect($capabilityDisciplines)->map(fn ($d) => ['discipline' => $d, 'level' => 'Not Applicable'])->values()->all();
    $teamCapabilityInit = $teamCapExisting['ratings'] ?? collect($capabilityDisciplines)->map(fn ($d) => ['discipline' => $d, 'level' => 'Not Applicable'])->values()->all();

    $equipmentInit = [];
    $equipmentOtherInit = [];
    foreach ($equipment as $key => $group) {
        $equipmentInit[$key] = $existingEquipment[$key]['checked'] ?? [];
        $equipmentOtherInit[$key] = $existingEquipment[$key]['other'] ?? '';
    }

    $riskRatios = collect($riskLevels)->map(fn ($r) => $r['ratio_per_responder'])->all();

    $topHazards = collect($existing->answers['hazard_ranks'] ?? [])
        ->filter(fn ($h) => $h['rank'] !== 'NONE')
        ->sortBy('rank')
        ->values();
@endphp
<x-app-layout>
    <div class="py-10"
        x-data="{
            showForm: {{ $existing ? 'false' : 'true' }},
            fullName: {{ Js::from($profile['full_name'] ?? auth()->user()->name) }},
            position: {{ Js::from($profile['position'] ?? '') }},
            region: {{ Js::from($profile['region'] ?? auth()->user()->region ?? '') }},
            lguLevel: {{ Js::from($profile['lgu_level'] ?? '') }},
            lguName: {{ Js::from($profile['lgu_name'] ?? auth()->user()->organization ?? '') }},
            population: {{ Js::from($profile['population'] ?? '') }},
            hazardRanks: {{ Js::from($hazardRanksInit) }},
            averageRiskLevel: {{ Js::from($org['average_risk_level'] ?? '') }},
            riskRatios: {{ Js::from($riskRatios) }},
            organizedTeams: {{ Js::from($organizedTeamsInit) }},
            personnelCompetency: {{ Js::from($personnelCompetencyInit) }},
            totalRespondersBasic: {{ Js::from($org['total_responders_basic'] ?? '') }},
            deployableRespondersTechnician: {{ Js::from($org['deployable_responders_technician'] ?? '') }},
            teamAssignedLeadResponders: {{ Js::from($org['team_assigned_lead_responders'] ?? '') }},
            files: [],
            teamCapability: {{ Js::from($teamCapabilityInit) }},
            srrTeamsCount: {{ Js::from($teamCapExisting['srr_teams_count'] ?? '') }},
            advancedTeamsCount: {{ Js::from($teamCapExisting['advanced_teams_count'] ?? '') }},
            trainingsNeededResponseTeams: {{ Js::from($teamCapExisting['trainings_needed_response_teams'] ?? '') }},
            trainingsUndertaken: {{ Js::from($teamCapExisting['trainings_undertaken'] ?? '') }},
            trainingsNeededImt: {{ Js::from($teamCapExisting['trainings_needed_imt'] ?? '') }},
            trainingsNeededEoc: {{ Js::from($teamCapExisting['trainings_needed_eoc'] ?? '') }},
            otherTeamsOrganized: {{ Js::from($teamCapExisting['other_teams_organized'] ?? '') }},
            trainingsNeededOtherTeams: {{ Js::from($teamCapExisting['trainings_needed_other_teams'] ?? '') }},
            equipment: {{ Js::from($equipmentInit) }},
            equipmentOther: {{ Js::from($equipmentOtherInit) }},
            operationalSystemsChecked: {{ Js::from($existing->answers['operational_systems']['checked'] ?? []) }},
            otherOperationalSystems: {{ Js::from($existing->answers['operational_systems']['other'] ?? '') }},
            simulationExercises: {{ Js::from($existing->answers['operational_systems']['simulation_exercises'] ?? '') }},
            trainingsNeededSops: {{ Js::from($existing->answers['operational_systems']['trainings_needed'] ?? '') }},
            sustainmentChecklist: {{ Js::from($existing->answers['sustainment']['checked'] ?? []) }},
            otherSustainmentDocuments: {{ Js::from($existing->answers['sustainment']['other'] ?? '') }},
            trainingsNeededSustainment: {{ Js::from($existing->answers['sustainment']['trainings_needed'] ?? '') }},
            submitting: false,
            error: null,
            toggle(list, value) {
                const i = list.indexOf(value);
                if (i === -1) list.push(value); else list.splice(i, 1);
            },
            baselineResponders() {
                const ratio = this.riskRatios[this.averageRiskLevel];
                if (!ratio || !this.population) return null;
                return Math.ceil(this.population / ratio);
            },
            async submit() {
                if (!this.fullName || !this.position || !this.region || !this.lguLevel || !this.lguName || !this.population || !this.averageRiskLevel) {
                    this.error = @js(__('Please complete the LGU profile and select an average risk level.'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                if (this.files.length === 0) {
                    this.error = @js(__('Please upload at least one Team Fact Sheet file.'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                this.error = null;
                this.submitting = true;
                try {
                    const fd = new FormData();
                    fd.append('full_name', this.fullName);
                    fd.append('position', this.position);
                    fd.append('region', this.region);
                    fd.append('lgu_level', this.lguLevel);
                    fd.append('lgu_name', this.lguName);
                    fd.append('population', this.population);
                    this.hazardRanks.forEach((h, i) => {
                        fd.append(`hazard_ranks[${i}][hazard]`, h.hazard);
                        fd.append(`hazard_ranks[${i}][rank]`, h.rank);
                    });
                    fd.append('average_risk_level', this.averageRiskLevel);
                    this.organizedTeams.forEach((t, i) => {
                        fd.append(`organized_teams[${i}][discipline]`, t.discipline);
                        fd.append(`organized_teams[${i}][size]`, t.size);
                    });
                    this.personnelCompetency.forEach((p, i) => {
                        fd.append(`personnel_competency[${i}][discipline]`, p.discipline);
                        fd.append(`personnel_competency[${i}][level]`, p.level);
                    });
                    fd.append('total_responders_basic', this.totalRespondersBasic);
                    fd.append('deployable_responders_technician', this.deployableRespondersTechnician);
                    fd.append('team_assigned_lead_responders', this.teamAssignedLeadResponders);
                    this.files.forEach(f => fd.append('team_fact_sheet_files[]', f));
                    this.teamCapability.forEach((t, i) => {
                        fd.append(`team_capability[${i}][discipline]`, t.discipline);
                        fd.append(`team_capability[${i}][level]`, t.level);
                    });
                    fd.append('srr_teams_count', this.srrTeamsCount);
                    fd.append('advanced_teams_count', this.advancedTeamsCount);
                    fd.append('trainings_needed_response_teams', this.trainingsNeededResponseTeams);
                    fd.append('trainings_undertaken', this.trainingsUndertaken);
                    fd.append('trainings_needed_imt', this.trainingsNeededImt);
                    fd.append('trainings_needed_eoc', this.trainingsNeededEoc);
                    fd.append('other_teams_organized', this.otherTeamsOrganized);
                    fd.append('trainings_needed_other_teams', this.trainingsNeededOtherTeams);

                    Object.keys(this.equipment).forEach(key => {
                        this.equipment[key].forEach(v => fd.append(`${key}[]`, v));
                        fd.append(`other_${key}`, this.equipmentOther[key] ?? '');
                    });

                    this.operationalSystemsChecked.forEach(v => fd.append('operational_systems[]', v));
                    fd.append('other_operational_systems', this.otherOperationalSystems);
                    fd.append('simulation_exercises', this.simulationExercises);
                    fd.append('trainings_needed_sops', this.trainingsNeededSops);

                    this.sustainmentChecklist.forEach(v => fd.append('sustainment_checklist[]', v));
                    fd.append('other_sustainment_documents', this.otherSustainmentDocuments);
                    fd.append('trainings_needed_sustainment', this.trainingsNeededSustainment);

                    const res = await fetch('{{ route('training-needs-assessment.lgu.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });

                    if (!res.ok) {
                        let msg = @js(__('Something went wrong submitting your assessment. Please check the required fields and try again.'));
                        try {
                            const body = await res.json();
                            if (body.errors) msg = Object.values(body.errors).flat().join(' ');
                            else if (body.message) msg = body.message;
                        } catch (e) {}
                        throw new Error(msg);
                    }

                    window.location.reload();
                } catch (e) {
                    this.error = e.message;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } finally {
                    this.submitting = false;
                }
            },
        }"
    >
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white mb-6">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Dashboard') }}
            </a>

            <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Needs Assessment') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('Baseline Data Tool for Responders Competency-Capability Assessment in the Local Government Units.') }}</p>

            <template x-if="error">
                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-300" x-text="error"></div>
            </template>

            @if ($existing)
                <!-- ALREADY SUBMITTED SUMMARY -->
                <div x-show="!showForm" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 text-center">
                        <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-full bg-[#152A4E]/8 dark:bg-[#152A4E]/30">
                            <svg class="w-7 h-7 text-[#152A4E]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-[#152A4E] dark:text-white mb-2">{{ __('Baseline Data Submitted') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            {{ __('Submitted') }} {{ $existing->created_at->format('M j, Y') }} — {{ __('Your OCD Regional Office will review this to plan capacity-building support.') }}
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                        <h3 class="text-base font-bold text-[#152A4E] dark:text-white mb-4">{{ __('Summary') }}</h3>
                        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('LGU') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $profile['lgu_level'] ?? '—' }} — {{ $profile['lgu_name'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Region') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $profile['region'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Population') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ isset($profile['population']) ? number_format($profile['population']) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Average Risk Level') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $org['average_risk_level'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Baseline Responders Needed') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $org['baseline_responders'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Top Hazards') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">
                                    @forelse ($topHazards as $h)
                                        {{ $h['rank'] }}: {{ $h['hazard'] }}@if (!$loop->last), @endif
                                    @empty
                                        —
                                    @endforelse
                                </dd>
                            </div>
                        </dl>

                        @if (!empty($org['team_fact_sheet_files']))
                            <div class="mt-6">
                                <dt class="text-gray-500 dark:text-gray-400 text-sm mb-2">{{ __('Team Fact Sheet Files') }}</dt>
                                <ul class="space-y-1">
                                    @foreach ($org['team_fact_sheet_files'] as $path)
                                        <li>
                                            <a href="{{ asset('storage/'.$path) }}" target="_blank" class="text-sm text-[#152A4E] dark:text-white hover:underline">
                                                {{ basename($path) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-center">
                        <button type="button" @click="showForm = true; window.scrollTo({top:0, behavior:'smooth'})"
                            class="text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white px-6 py-3">
                            {{ __('Submit Updated Assessment') }}
                        </button>
                    </div>
                </div>
            @endif

            <!-- FORM -->
            <div x-show="showForm" class="space-y-8">

                <!-- LGU Demographics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-4">{{ __('LGU Demographics') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Full Name of Respondent') }}</label>
                            <input type="text" x-model="fullName" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Position') }}</label>
                            <input type="text" x-model="position" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Region') }}</label>
                            <select x-model="region" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                                <option value="" disabled>{{ __('Select') }}</option>
                                @foreach ($regions as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Local Government Unit Level') }}</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach ($lguLevels as $level)
                                    <button type="button" @click="lguLevel = {{ Js::from($level) }}"
                                        :class="lguLevel === {{ Js::from($level) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                        class="w-full px-4 py-2 rounded-lg border-2 text-sm font-semibold text-center transition">{{ $level }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Provincial / City / Municipal / Barangay Name') }}</label>
                            <input type="text" x-model="lguName" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Population') }}</label>
                            <input type="number" min="1" x-model="population" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                    </div>
                </div>

                <!-- Hazard/Risk Profile -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Hazard/Risk Profile') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Based on your CDRA/CP, what are the top three (3) hazards identified in your LGU?') }}</p>
                    <div class="space-y-3">
                        <template x-for="(h, index) in hazardRanks" :key="h.hazard">
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3" x-text="h.hazard"></p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    @foreach ($hazardRanks as $rank)
                                        <button type="button" @click="hazardRanks[index].rank = {{ Js::from($rank) }}"
                                            :class="hazardRanks[index].rank === {{ Js::from($rank) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                            class="w-full px-3 py-1.5 rounded-lg border-2 text-xs font-semibold text-center transition">{{ $rank }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Assessment Pillar I: Organization -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Assessment Pillar I: Organization') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Structure, roles, and existing standard operating procedures.') }}</p>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Average Risk Level') }}</label>
                        <div class="space-y-2">
                            @foreach ($riskLevels as $name => $risk)
                                <label class="flex items-start gap-3 p-3 rounded-lg border-2 cursor-pointer transition"
                                    :class="averageRiskLevel === {{ Js::from($name) }} ? 'border-[#152A4E]' : 'border-gray-200 dark:border-gray-600'">
                                    <input type="radio" name="average_risk_level" value="{{ $name }}" x-model="averageRiskLevel" class="mt-1 text-[#152A4E] focus:ring-[#152A4E]">
                                    <span class="text-sm text-gray-700 dark:text-gray-200"><strong>{{ $name }}</strong> — {{ $risk['description'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-6 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 p-4">
                        <p class="text-sm font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Baseline of Responder over Population Ratio') }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">{{ __('Formula: Baseline number of Responders = Population × Risk-based Responder Ratio') }}</p>

                        <template x-if="population && averageRiskLevel">
                            <div class="text-sm text-gray-700 dark:text-gray-200 space-y-1.5">
                                <p>
                                    <span x-text="Number(population).toLocaleString()"></span> {{ __('people') }}
                                    &times; (1 {{ __('responder') }} / <span x-text="riskRatios[averageRiskLevel]"></span> {{ __('people') }})
                                    — <strong x-text="averageRiskLevel"></strong>
                                </p>
                                <p class="text-base font-bold text-[#152A4E] dark:text-white">
                                    {{ __('Baseline # of responders') }} = <span x-text="baselineResponders()"></span> {{ __('responders to be capacitated') }}
                                </p>
                            </div>
                        </template>
                        <template x-if="!population || !averageRiskLevel">
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Enter Population above and select an Average Risk Level to see this computed automatically.') }}</p>
                        </template>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Organized Team/s for Deployment') }}</label>
                        <div class="space-y-3">
                            <template x-for="(t, index) in organizedTeams" :key="t.discipline">
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3" x-text="t.discipline"></p>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        @foreach ($teamSizes as $size)
                                            <button type="button" @click="organizedTeams[index].size = {{ Js::from($size) }}"
                                                :class="organizedTeams[index].size === {{ Js::from($size) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                                class="w-full px-3 py-1.5 rounded-lg border-2 text-xs font-semibold text-center transition">{{ $size }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Personnel Competency') }}</label>
                        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1 mb-3">
                            <li><strong class="text-gray-700 dark:text-gray-300">{{ __('Basic') }}</strong> — {{ __('100% of the team has awareness/orientation skills (for IMT: ICS EC/Orientation/BICS)') }}</li>
                            <li><strong class="text-gray-700 dark:text-gray-300">{{ __('Technician') }}</strong> — {{ __('at least 60% of personnel trained are operationally proficient (for IMT: ICS Level 2-3)') }}</li>
                            <li><strong class="text-gray-700 dark:text-gray-300">{{ __('Specialist') }}</strong> — {{ __('at least 15% of the team has advanced skills and leadership (for ICS Level 4-TFI)') }}</li>
                        </ul>
                        <div class="space-y-3">
                            <template x-for="(p, index) in personnelCompetency" :key="p.discipline">
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3" x-text="p.discipline"></p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach ($personnelCompetencyLevels as $level)
                                            <button type="button" @click="personnelCompetency[index].level = {{ Js::from($level) }}"
                                                :class="personnelCompetency[index].level === {{ Js::from($level) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                                class="w-full px-3 py-1.5 rounded-lg border-2 text-xs font-semibold text-center transition">{{ $level }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="flex items-end min-h-[2rem] text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Total Responders (Basic Level)') }}</label>
                            <input type="number" min="0" x-model="totalRespondersBasic" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="flex items-end min-h-[2rem] text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Deployable Responders (Technician)') }}</label>
                            <input type="number" min="0" x-model="deployableRespondersTechnician" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="flex items-end min-h-[2rem] text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Team-assigned Lead Responders (Specialist)') }}</label>
                            <input type="number" min="0" x-model="teamAssignedLeadResponders" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Upload Team Fact Sheet (with organization list, roles & responsibilities)') }}</label>
                        <input type="file" multiple accept=".pdf,.xls,.xlsx,.csv" @change="files = Array.from($event.target.files)"
                            class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[#152A4E] file:text-white file:text-sm file:font-semibold">
                        <p class="text-xs text-gray-400 mt-1">{{ __('Up to 5 files, PDF or spreadsheet, max 10MB each.') }}</p>
                        <ul class="mt-2 space-y-1">
                            <template x-for="(f, index) in files" :key="index">
                                <li class="text-sm text-gray-600 dark:text-gray-300" x-text="f.name"></li>
                            </template>
                        </ul>
                    </div>
                </div>

                <!-- Assessment Pillar II: Team Capability -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Assessment Pillar II: Team Capability') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Training levels and skills. Write N/A if not applicable.') }}</p>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Team Capability (choose 1 per discipline)') }}</label>
                        <div class="space-y-3">
                            <template x-for="(t, index) in teamCapability" :key="t.discipline">
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3" x-text="t.discipline"></p>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        @foreach ($teamCapabilityLevels as $level)
                                            <button type="button" @click="teamCapability[index].level = {{ Js::from($level) }}"
                                                :class="teamCapability[index].level === {{ Js::from($level) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                                class="w-full px-3 py-1.5 rounded-lg border-2 text-xs font-semibold text-center transition">{{ $level }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Number of SRR Teams (Operational Capability) — specify number per discipline/kind') }}</label>
                            <textarea x-model="srrTeamsCount" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Number of Teams with Advanced Capability — specify kind and number') }}</label>
                            <textarea x-model="advancedTeamsCount" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings needed for skills upgrading of Response teams') }}</label>
                            <textarea x-model="trainingsNeededResponseTeams" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings undertaken by at least 50-60% of the total number of responders') }}</label>
                            <textarea x-model="trainingsUndertaken" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings needed for skills upgrading of Incident Management teams') }}</label>
                            <textarea x-model="trainingsNeededImt" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings needed for skills upgrading of Emergency Operations Center personnel') }}</label>
                            <textarea x-model="trainingsNeededEoc" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Other teams organized (not stated above)') }}</label>
                            <textarea x-model="otherTeamsOrganized" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings needed for skills upgrading of other teams') }}</label>
                            <textarea x-model="trainingsNeededOtherTeams" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Assessment Pillar III: Equipment Capability -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Assessment Pillar III: Equipment Capability') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Available and serviceable equipment, vehicles, and tools.') }}</p>

                    <div class="space-y-6">
                        @foreach ($equipment as $key => $group)
                            <div>
                                <label class="block text-sm font-semibold text-[#152A4E] dark:text-white mb-2">{{ $group['label'] }}</label>
                                <div class="grid sm:grid-cols-2 gap-2 mb-3">
                                    @foreach ($group['items'] as $item)
                                        <label class="flex items-start gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-[#152A4E] cursor-pointer">
                                            <input type="checkbox" value="{{ $item }}"
                                                :checked="equipment['{{ $key }}'].includes({{ Js::from($item) }})"
                                                @change="toggle(equipment['{{ $key }}'], {{ Js::from($item) }})"
                                                class="mt-0.5 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                            <span class="text-xs text-gray-700 dark:text-gray-200">{{ $item }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <input type="text" x-model="equipmentOther['{{ $key }}']" placeholder="{{ __('Other :label not listed above (write N/A if none)', ['label' => $group['label']]) }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm">
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Assessment Pillar IV: Operational Systems -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Assessment Pillar IV: Operational Systems') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Standard operating procedures, protocols, and plans for response operations.') }}</p>

                    <div class="grid sm:grid-cols-2 gap-2 mb-3">
                        @foreach ($operationalSystems as $item)
                            <label class="flex items-start gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-[#152A4E] cursor-pointer">
                                <input type="checkbox" value="{{ $item }}"
                                    :checked="operationalSystemsChecked.includes({{ Js::from($item) }})"
                                    @change="toggle(operationalSystemsChecked, {{ Js::from($item) }})"
                                    class="mt-0.5 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-xs text-gray-700 dark:text-gray-200">{{ $item }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('List other operational systems not listed above') }}</label>
                            <textarea x-model="otherOperationalSystems" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('List conducted simulation exercises for the documents developed above') }}</label>
                            <textarea x-model="simulationExercises" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings needed for development of SOPs, protocols, and plans') }}</label>
                            <textarea x-model="trainingsNeededSops" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Assessment Pillar V: Sustainment and Logistics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Assessment Pillar V: Sustainment and Logistics') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Supply availability, maintenance, and mobility.') }}</p>

                    <div class="grid sm:grid-cols-2 gap-2 mb-3">
                        @foreach ($sustainmentItems as $item)
                            <label class="flex items-start gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-[#152A4E] cursor-pointer">
                                <input type="checkbox" value="{{ $item }}"
                                    :checked="sustainmentChecklist.includes({{ Js::from($item) }})"
                                    @change="toggle(sustainmentChecklist, {{ Js::from($item) }})"
                                    class="mt-0.5 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-xs text-gray-700 dark:text-gray-200">{{ $item }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('List other documents to verify sustainment and logistics compliance (write N/A if not applicable)') }}</label>
                            <textarea x-model="otherSustainmentDocuments" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Trainings needed for upgrading of existing sustainment and logistics systems') }}</label>
                            <textarea x-model="trainingsNeededSustainment" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" @click="submit()" :disabled="submitting"
                        class="px-8 py-3 rounded-lg bg-[#152A4E] text-white text-sm font-semibold hover:bg-[#0f1d38] disabled:opacity-50 transition">
                        <span x-show="!submitting">{{ __('Submit Assessment') }}</span>
                        <span x-show="submitting">{{ __('Submitting…') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
