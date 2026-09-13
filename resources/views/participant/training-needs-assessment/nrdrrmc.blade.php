@php
    $facetCount = collect($facetGroups)->sum(fn ($group) => count($group['entries']));
@endphp
<x-app-layout>
    <div class="py-10"
        x-data="{
            step: 'form',
            institution: {{ Js::from(old('institution', $existing->answers['profile']['institution'] ?? old('organization', auth()->user()->organization ?? ''))) }},
            position: {{ Js::from(old('position', $existing->answers['profile']['position'] ?? '')) }},
            responsibilities: {{ Js::from($existing->answers['responsibilities'] ?? []) }},
            competencies: {{ Js::from(collect($facetGroups)->flatMap(fn ($g) => collect($g['entries'])->map(fn ($e) => ['facet' => $e['facet'], 'competency' => null, 'importance' => null]))->values()->all()) }},
            openResponses: {{ Js::from(collect($openQuestions)->map(fn ($q) => ['question' => $q, 'answer' => ''])->values()->all()) }},
            openDomains: {{ Js::from(collect($facetGroups)->keys()->mapWithKeys(fn ($i) => [(string) $i => $i === 0])->all()) }},
            results: {{ Js::from($existing->answers['competencies'] ?? null) }},
            showAllResults: false,
            submitting: false,
            error: null,
            init() {
                if (this.results) this.step = 'results';
            },
            toggleResponsibility(r) {
                const i = this.responsibilities.indexOf(r);
                if (i === -1) this.responsibilities.push(r); else this.responsibilities.splice(i, 1);
            },
            ratedCount() {
                return this.competencies.filter(c => c.competency && c.importance).length;
            },
            allRated() {
                return this.competencies.every(c => c.competency && c.importance);
            },
            retake() {
                this.step = 'form';
                this.results = null;
                this.showAllResults = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            priorityClasses(label) {
                return {
                    'Extremely High Priority': 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                    'High Priority': 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                    'Moderate Priority': 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                    'Low Priority': 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                    'Not a Priority': 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                    'Little to No Training Needed': 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                }[label] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
            },
            visibleResults() {
                return this.showAllResults ? this.results : (this.results || []).slice(0, 20);
            },
            async submit() {
                if (!this.institution || !this.position || !this.allRated()) {
                    this.error = @js(__('Please fill in your institution and position, and rate every item in the Functional Competencies Analysis before submitting.'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                this.error = null;
                this.submitting = true;
                try {
                    const res = await fetch('{{ route('training-needs-assessment.nrdrrmc.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            institution: this.institution,
                            position: this.position,
                            responsibilities: this.responsibilities,
                            competencies: this.competencies,
                            open_responses: this.openResponses,
                        }),
                    });
                    if (!res.ok) throw new Error('failed');
                    const data = await res.json();
                    this.results = data.competencies;
                    this.step = 'results';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (e) {
                    this.error = @js(__('Something went wrong submitting your assessment. Please try again.'));
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
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('TNA Tool for National and Regional DRRM Council Members — answer honestly so we can identify the training you and your council need most.') }}</p>

            <template x-if="error">
                <div class="mb-6 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-300" x-text="error"></div>
            </template>

            <!-- FORM -->
            <div x-show="step === 'form'" class="space-y-8">

                <!-- Participant's Profile -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-4">{{ __("Participant's Profile") }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Name') }}</label>
                            <input type="text" disabled value="{{ auth()->user()->name }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-base py-3">
                        </div>
                        <div>
                            <label for="institution" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Office / Institution') }}</label>
                            <input type="text" id="institution" x-model="institution" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Position / Title') }}</label>
                            <input type="text" id="position" x-model="position" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-base py-3">
                        </div>
                    </div>
                </div>

                <!-- Current Role and Responsibilities -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Current Role and Responsibilities') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Check all that apply to you and your current roles/responsibilities in your office/institution.') }}</p>
                    <div class="space-y-2">
                        @foreach ($responsibilities as $item)
                            <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-[#152A4E] cursor-pointer">
                                <input type="checkbox" value="{{ $item }}"
                                    :checked="responsibilities.includes({{ Js::from($item) }})"
                                    @change="toggleResponsibility({{ Js::from($item) }})"
                                    class="mt-1 rounded border-gray-300 text-[#152A4E] focus:ring-[#152A4E]">
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $item }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Functional Competencies Analysis -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Functional Competencies Analysis') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('For each statement, rate your current competency level and how important it is to your current role.') }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">{{ __('Competency: 1 = Basic … 5 = Excellent.  Importance: 1 = Not Important … 5 = Very Important.') }}</p>
                    <p class="text-xs font-semibold text-[#152A4E] dark:text-white mb-6">
                        <span x-text="ratedCount()"></span> / {{ $facetCount }} {{ __('rated') }}
                    </p>

                    <div class="space-y-3">
                        @foreach ($facetGroups as $domainIndex => $group)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                <button type="button" @click="openDomains[{{ $domainIndex }}] = !openDomains[{{ $domainIndex }}]"
                                    class="w-full flex items-center justify-between gap-3 p-4 text-left bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ $group['domain'] }}</span>
                                    <span class="text-xs text-gray-400 shrink-0" x-text="openDomains[{{ $domainIndex }}] ? '{{ __('Hide') }}' : '{{ __('Show') }} ({{ count($group['entries']) }})'"></span>
                                </button>

                                <div x-show="openDomains[{{ $domainIndex }}]" class="p-4 space-y-4">
                                    @foreach ($group['entries'] as $entry)
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">{{ $entry['item'] }}</p>

                                            <div class="grid sm:grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Competency') }}</p>
                                                    <div class="flex gap-1.5">
                                                        @for ($n = 1; $n <= 5; $n++)
                                                            <button type="button" @click="competencies[{{ $entry['index'] }}].competency = {{ $n }}"
                                                                :class="competencies[{{ $entry['index'] }}].competency === {{ $n }} ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-[#152A4E]'"
                                                                class="w-9 h-9 rounded-lg border-2 text-sm font-semibold transition">{{ $n }}</button>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Importance') }}</p>
                                                    <div class="flex gap-1.5">
                                                        @for ($n = 1; $n <= 5; $n++)
                                                            <button type="button" @click="competencies[{{ $entry['index'] }}].importance = {{ $n }}"
                                                                :class="competencies[{{ $entry['index'] }}].importance === {{ $n }} ? 'bg-[#E2762D] text-white border-[#E2762D]' : 'border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-[#E2762D]'"
                                                                class="w-9 h-9 rounded-lg border-2 text-sm font-semibold transition">{{ $n }}</button>
                                                        @endfor
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Knowledge and Skills Gaps -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-4">{{ __('Knowledge and Skills Gaps') }}</h2>
                    <div class="space-y-5">
                        <template x-for="(item, index) in openResponses" :key="index">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" x-text="(index + 1) + '. ' + item.question"></label>
                                <textarea x-model="item.answer" rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm"></textarea>
                            </div>
                        </template>
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

            <!-- RESULTS -->
            <div x-show="step === 'results'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 text-center">
                    <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-full bg-[#152A4E]/8 dark:bg-[#152A4E]/30">
                        <svg class="w-7 h-7 text-[#152A4E]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-[#152A4E] dark:text-white mb-2">{{ __('Assessment Submitted') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        {{ __('Thank you. Your OCD Regional Office will review this to help plan your training.') }}
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h3 class="text-base font-bold text-[#152A4E] dark:text-white mb-4">{{ __('Your Training Priorities') }}</h3>
                    <div class="space-y-2">
                        <template x-for="(c, index) in visibleResults()" :key="index">
                            <div class="flex items-start justify-between gap-4 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                                <p class="text-sm text-gray-700 dark:text-gray-200" x-text="c.facet"></p>
                                <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide rounded-full px-2.5 py-1" :class="priorityClasses(c.priority)" x-text="c.priority"></span>
                            </div>
                        </template>
                    </div>
                    <template x-if="results && results.length > 20">
                        <button type="button" @click="showAllResults = !showAllResults"
                            class="mt-4 text-sm font-semibold text-[#152A4E] dark:text-white hover:underline">
                            <span x-show="!showAllResults">{{ __('Show all') }} <span x-text="results.length"></span></span>
                            <span x-show="showAllResults">{{ __('Show top 20 only') }}</span>
                        </button>
                    </template>
                </div>

                <div class="flex justify-center">
                    <button type="button" @click="retake()"
                        class="text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white px-6 py-3">
                        {{ __('Retake Assessment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
