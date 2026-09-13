@php
    $profile = $existing->answers['profile'] ?? [];
    $volProfile = $existing->answers['volunteer_profile'] ?? [];
    $capacity = $existing->answers['capacity'] ?? [];
    $mobilization = $existing->answers['mobilization'] ?? [];
    $deploymentsExisting = $existing->answers['deployments'] ?? ['', '', ''];
    $gapAnalysis = $existing->answers['gap_analysis'] ?? [];

    $specializationValuesInit = isset($capacity['specializations'])
        ? collect($capacity['specializations'])->pluck('value')->values()->all()
        : array_fill(0, count($specializations), '');

    $labelCounts = [];
    $specializationDisplayLabels = collect($specializations)->map(function ($label) use (&$labelCounts) {
        $labelCounts[$label] = ($labelCounts[$label] ?? 0) + 1;

        return $labelCounts[$label] > 1 ? $label.' ('.$labelCounts[$label].')' : $label;
    });
@endphp
<x-app-layout>
    <div class="py-10"
        x-data="{
            showForm: {{ $existing ? 'false' : 'true' }},
            fullName: {{ Js::from($profile['full_name'] ?? auth()->user()->name) }},
            position: {{ Js::from($profile['position'] ?? '') }},
            contactNumber: {{ Js::from($profile['contact_number'] ?? auth()->user()->mobile_number ?? '') }},
            groupName: {{ Js::from($profile['group_name'] ?? auth()->user()->organization ?? '') }},
            level: {{ Js::from($profile['level'] ?? '') }},
            accreditationStatus: {{ Js::from($profile['accreditation_status'] ?? '') }},
            accreditingAuthority: {{ Js::from($profile['accrediting_authority'] ?? '') }},
            accreditationDate: {{ Js::from($profile['accreditation_date'] ?? '') }},
            region: {{ Js::from($profile['region'] ?? '') }},
            activeVolunteers: {{ Js::from($volProfile['active_volunteers'] ?? '') }},
            age1820: {{ Js::from($volProfile['age_18_20'] ?? '') }},
            age2130: {{ Js::from($volProfile['age_21_30'] ?? '') }},
            age3140: {{ Js::from($volProfile['age_31_40'] ?? '') }},
            age4150: {{ Js::from($volProfile['age_41_50'] ?? '') }},
            age5160: {{ Js::from($volProfile['age_51_60'] ?? '') }},
            maleCount: {{ Js::from($volProfile['male_count'] ?? '') }},
            femaleCount: {{ Js::from($volProfile['female_count'] ?? '') }},
            specializations: {{ Js::from($specializationValuesInit) }},
            otherSpecializations: {{ Js::from($capacity['other_specializations'] ?? '') }},
            trainingsCompleted: {{ Js::from($capacity['trainings_completed'] ?? []) }},
            otherTrainingsCompleted: {{ Js::from($capacity['other_trainings_completed'] ?? '') }},
            coordinationMechanisms: {{ Js::from($mobilization['coordination_mechanisms'] ?? '') }},
            mobilizationReadiness: {{ Js::from($mobilization['readiness'] ?? []) }},
            transportationFile: null,
            equipmentFile: null,
            deployment1: {{ Js::from($deploymentsExisting[0] ?? '') }},
            deployment2: {{ Js::from($deploymentsExisting[1] ?? '') }},
            deployment3: {{ Js::from($deploymentsExisting[2] ?? '') }},
            identifiedGaps: {{ Js::from($gapAnalysis['identified_gaps'] ?? '') }},
            priorityNeeds: {{ Js::from($gapAnalysis['priority_needs'] ?? '') }},
            recommendationsCollaboration: {{ Js::from($gapAnalysis['recommendations_collaboration'] ?? '') }},
            submitting: false,
            error: null,
            toggle(list, value) {
                const i = list.indexOf(value);
                if (i === -1) list.push(value); else list.splice(i, 1);
            },
            async submit() {
                if (!this.fullName || !this.position || !this.groupName || !this.level || !this.region) {
                    this.error = @js(__('Please complete the demographics section.'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                if (!this.transportationFile || !this.equipmentFile) {
                    this.error = @js(__('Please upload both the Transportation Assets list and the Major Response Equipment list.'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                this.error = null;
                this.submitting = true;
                try {
                    const fd = new FormData();
                    fd.append('full_name', this.fullName);
                    fd.append('position', this.position);
                    fd.append('contact_number', this.contactNumber);
                    fd.append('group_name', this.groupName);
                    fd.append('level', this.level);
                    fd.append('accreditation_status', this.accreditationStatus);
                    fd.append('accrediting_authority', this.accreditingAuthority);
                    fd.append('accreditation_date', this.accreditationDate);
                    fd.append('region', this.region);
                    fd.append('active_volunteers', this.activeVolunteers);
                    fd.append('age_18_20', this.age1820);
                    fd.append('age_21_30', this.age2130);
                    fd.append('age_31_40', this.age3140);
                    fd.append('age_41_50', this.age4150);
                    fd.append('age_51_60', this.age5160);
                    fd.append('male_count', this.maleCount);
                    fd.append('female_count', this.femaleCount);
                    this.specializations.forEach((v, i) => fd.append(`specializations[${i}]`, v));
                    fd.append('other_specializations', this.otherSpecializations);
                    this.trainingsCompleted.forEach(v => fd.append('trainings_completed[]', v));
                    fd.append('other_trainings_completed', this.otherTrainingsCompleted);
                    fd.append('coordination_mechanisms', this.coordinationMechanisms);
                    this.mobilizationReadiness.forEach(v => fd.append('mobilization_readiness[]', v));
                    fd.append('transportation_assets_file', this.transportationFile);
                    fd.append('response_equipment_file', this.equipmentFile);
                    fd.append('deployment_1', this.deployment1);
                    fd.append('deployment_2', this.deployment2);
                    fd.append('deployment_3', this.deployment3);
                    fd.append('identified_gaps', this.identifiedGaps);
                    fd.append('priority_needs', this.priorityNeeds);
                    fd.append('recommendations_collaboration', this.recommendationsCollaboration);

                    const res = await fetch('{{ route('training-needs-assessment.volunteer.store') }}', {
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
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('Volunteers Baseline Data FY 2026 — complete all six sections with accurate information.') }}</p>

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
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Volunteer Group') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $profile['group_name'] ?? '—' }} ({{ $profile['level'] ?? '—' }})</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Region') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $profile['region'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Accreditation') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $profile['accreditation_status'] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Active Volunteers') }}</dt>
                                <dd class="font-medium text-gray-700 dark:text-gray-200">{{ $volProfile['active_volunteers'] ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if (!empty($mobilization['transportation_assets_file']) || !empty($mobilization['response_equipment_file']))
                            <div class="mt-6">
                                <dt class="text-gray-500 dark:text-gray-400 text-sm mb-2">{{ __('Uploaded Files') }}</dt>
                                <ul class="space-y-1">
                                    @if (!empty($mobilization['transportation_assets_file']))
                                        <li><a href="{{ asset('storage/'.$mobilization['transportation_assets_file']) }}" target="_blank" class="text-sm text-[#152A4E] dark:text-white hover:underline">{{ __('Transportation Assets List') }}</a></li>
                                    @endif
                                    @if (!empty($mobilization['response_equipment_file']))
                                        <li><a href="{{ asset('storage/'.$mobilization['response_equipment_file']) }}" target="_blank" class="text-sm text-[#152A4E] dark:text-white hover:underline">{{ __('Major Response Equipment List') }}</a></li>
                                    @endif
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

                <!-- Section 1: Demographics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-4">{{ __('Section 1: Demographics') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Full Name of Contact Person (First Name, Middle Initial, Last Name)') }}</label>
                            <input type="text" x-model="fullName" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Position') }}</label>
                            <input type="text" x-model="position" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Contact Details (office number)') }}</label>
                            <input type="text" x-model="contactNumber" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Name of Volunteer Group') }}</label>
                            <input type="text" x-model="groupName" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Level of Volunteer Group') }}</label>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                @foreach ($levels as $lvl)
                                    <button type="button" @click="level = {{ Js::from($lvl) }}"
                                        :class="level === {{ Js::from($lvl) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                        class="w-full px-3 py-2 rounded-lg border-2 text-sm font-semibold text-center transition">{{ $lvl }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Accreditation Status (as ACDV)') }}</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($accreditationStatuses as $status)
                                    <button type="button" @click="accreditationStatus = {{ Js::from($status) }}"
                                        :class="accreditationStatus === {{ Js::from($status) }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                        class="w-full px-3 py-2 rounded-lg border-2 text-sm font-semibold text-center transition">{{ $status }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Accrediting Authority / LGU') }}</label>
                            <input type="text" x-model="accreditingAuthority" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Accreditation Date') }}</label>
                            <input type="date" x-model="accreditationDate" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
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
                    </div>
                </div>

                <!-- Section 2: Volunteer Profile -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Section 2: Volunteer Profile') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Write N/A if not applicable.') }}</p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Number of Active Volunteers') }}</label>
                            <input type="text" x-model="activeVolunteers" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Age Range Distribution (18-20 years old)') }}</label>
                            <input type="text" x-model="age1820" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Age Range Distribution (21-30 years old)') }}</label>
                            <input type="text" x-model="age2130" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Age Range Distribution (31-40 years old)') }}</label>
                            <input type="text" x-model="age3140" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Age Range Distribution (41-50 years old)') }}</label>
                            <input type="text" x-model="age4150" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Age Range Distribution (51-60 years old)') }}</label>
                            <input type="text" x-model="age5160" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Sex Distribution (Number of Male)') }}</label>
                            <input type="text" x-model="maleCount" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Sex Distribution (Number of Female)') }}</label>
                            <input type="text" x-model="femaleCount" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Capacity Assessment -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Section 3: Capacity Assessment') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Provide the number of organized individual/functional teams/units per skills and specialization. Type N/A if not applicable.') }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">{{ __('e.g. "10 individuals / 2 functional teams with 5 members"') }}</p>

                    <div class="space-y-4 mb-4">
                        @foreach ($specializations as $index => $label)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $specializationDisplayLabels[$index] }}</label>
                                <input type="text" x-model="specializations[{{ $index }}]" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                            </div>
                        @endforeach
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Other Skills and Specializations not listed above (List down ALL)') }}</label>
                            <textarea x-model="otherSpecializations" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Training Completed (Check all that applies)') }}</label>
                        <div class="space-y-2 mb-4">
                            @foreach ($trainings as $item)
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-[#152A4E] cursor-pointer">
                                    <input type="checkbox" value="{{ $item }}"
                                        :checked="trainingsCompleted.includes({{ Js::from($item) }})"
                                        @change="toggle(trainingsCompleted, {{ Js::from($item) }})"
                                        class="mt-1 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">{{ $item }}</span>
                                </label>
                            @endforeach
                        </div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Other Trainings Completed (List down ALL)') }}</label>
                        <textarea x-model="otherTrainingsCompleted" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                    </div>
                </div>

                <!-- Section 4: Mobilization Readiness and Resources -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-4">{{ __('Section 4: Mobilization Readiness and Resources') }}</h2>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Coordination Mechanism/s') }}</label>
                        <textarea x-model="coordinationMechanisms" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Mobilization Readiness (check all that applies)') }}</label>
                        <div class="space-y-2">
                            @foreach ($mobilizationReadiness as $item)
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-[#152A4E] cursor-pointer">
                                    <input type="checkbox" value="{{ $item }}"
                                        :checked="mobilizationReadiness.includes({{ Js::from($item) }})"
                                        @change="toggle(mobilizationReadiness, {{ Js::from($item) }})"
                                        class="mt-1 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                    <span class="text-sm text-gray-700 dark:text-gray-200">{{ $item }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('List of Transportation Assets (signed PDF)') }}</label>
                            <input type="file" accept=".pdf" @change="transportationFile = $event.target.files[0] ?? null"
                                class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[#152A4E] file:text-white file:text-sm file:font-semibold">
                            <p class="text-xs text-gray-400 mt-1">{{ __('PDF only, max 100MB.') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1" x-show="transportationFile" x-text="transportationFile?.name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('List of Major Response Equipment (signed PDF)') }}</label>
                            <input type="file" accept=".pdf" @change="equipmentFile = $event.target.files[0] ?? null"
                                class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[#152A4E] file:text-white file:text-sm file:font-semibold">
                            <p class="text-xs text-gray-400 mt-1">{{ __('PDF only, max 100MB.') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1" x-show="equipmentFile" x-text="equipmentFile?.name"></p>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Previous Deployment Experiences -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Section 5: Previous Deployment Experiences') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Indicate whether the organization has previously participated in actual disaster or emergency operations, the date, and type of operations undertaken. Type N/A if not applicable.') }}</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Deployment 1') }}</label>
                            <textarea x-model="deployment1" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Deployment 2') }}</label>
                            <textarea x-model="deployment2" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Deployment 3') }}</label>
                            <textarea x-model="deployment3" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Gap Analysis -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-4">{{ __('Section 6: Gap Analysis') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Identified Gaps in Skills') }}</label>
                            <textarea x-model="identifiedGaps" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Priority Needs for Capacity Development') }}</label>
                            <textarea x-model="priorityNeeds" rows="2" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Recommendations for Collaborations') }}</label>
                            <textarea x-model="recommendationsCollaboration" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
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
