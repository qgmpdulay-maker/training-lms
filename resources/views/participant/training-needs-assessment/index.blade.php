{{--
    The assessment a participant fills in, rendered from the database: the
    questions come from the tna_* tables (TnaForm), which the Super Admin edits
    on admin/tna-forms. They used to be a hardcoded array at the top of this
    file, so changing a question meant changing code.

    One question per step. Scoring is the same as before: an option's points go
    to its category, the highest-scoring category picks the training, and an
    option's hours caps how long that training may run.
--}}
<x-app-layout>
    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white mb-6">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Dashboard') }}
            </a>

            <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ $form->label ?? __('Training Needs Assessment') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">
                {{ $form?->description ?: __('Answer a few simple questions and we will suggest a training for you.') }}
            </p>

            @if ($questions === [])
                {{-- No assessment is set up for this participant type yet. Better an
                     honest notice than an empty stepper. --}}
                <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('No assessment has been set up for your participant type yet. Your Regional Office will let you know when it is ready.') }}
                </div>
            @else
            <div
                x-data="{
                    questions: {{ Js::from($questions) }},
                    trainings: {{ Js::from($trainings) }},
                    step: 0,
                    answers: [],
                    // Working values for the kinds that aren't a single tap.
                    picked: [],
                    text: '',
                    competency: null,
                    importance: null,
                    finished: {{ $existingRecommendation ? 'true' : 'false' }},
                    recommendation: {{ Js::from($existingRecommendation) }},
                    get current() { return this.questions[this.step]; },
                    get canContinue() {
                        if (! this.current.is_required) return true;
                        if (this.current.kind === 'multi_choice') return this.picked.length > 0;
                        if (this.current.kind === 'competency') return this.competency !== null && this.importance !== null;
                        if (this.current.kind === 'short_text' || this.current.kind === 'number') return this.text !== '';
                        return false;
                    },
                    record(answer) {
                        this.answers[this.step] = answer;
                        this.picked = [];
                        this.text = '';
                        this.competency = null;
                        this.importance = null;

                        if (this.step < this.questions.length - 1) {
                            this.step++;
                        } else {
                            this.finish();
                        }
                    },
                    choose(option) {
                        this.record({
                            question: this.current.question,
                            kind: this.current.kind,
                            selected: option.label,
                            category: option.category ?? null,
                            points: option.points ?? null,
                            hours: option.hours ?? null,
                        });
                    },
                    toggle(option) {
                        const at = this.picked.findIndex((o) => o.label === option.label);
                        if (at === -1) { this.picked.push(option); } else { this.picked.splice(at, 1); }
                    },
                    isPicked(option) {
                        return this.picked.some((o) => o.label === option.label);
                    },
                    submitChecklist() {
                        // Every ticked option scores, so the points are summed and the
                        // category of the heaviest one is what the answer counts toward.
                        const scoring = this.picked.filter((o) => o.category);
                        const heaviest = scoring.reduce((top, o) => (! top || (o.points ?? 0) > (top.points ?? 0) ? o : top), null);
                        this.record({
                            question: this.current.question,
                            kind: this.current.kind,
                            selected: this.picked.map((o) => o.label).join('; '),
                            category: heaviest?.category ?? null,
                            points: scoring.reduce((sum, o) => sum + (o.points ?? 0), 0) || null,
                            hours: this.picked.reduce((max, o) => (o.hours ?? 0) > max ? o.hours : max, 0) || null,
                        });
                    },
                    submitCompetency() {
                        this.record({
                            question: this.current.question,
                            kind: this.current.kind,
                            selected: this.competency + ' / ' + this.importance,
                            category: null,
                            points: null,
                            hours: null,
                        });
                    },
                    submitText() {
                        this.record({
                            question: this.current.question,
                            kind: this.current.kind,
                            selected: this.text,
                            category: null,
                            points: null,
                            hours: null,
                        });
                    },
                    skip() {
                        this.record({
                            question: this.current.question,
                            kind: this.current.kind,
                            selected: '—',
                            category: null,
                            points: null,
                            hours: null,
                        });
                    },
                    back() {
                        if (this.finished) { this.finished = false; return; }
                        if (this.step > 0) this.step--;
                    },
                    restart() {
                        this.step = 0;
                        this.answers = [];
                        this.picked = [];
                        this.text = '';
                        this.competency = null;
                        this.importance = null;
                        this.finished = false;
                        this.recommendation = null;
                    },
                    finish() {
                        const scores = {};
                        let maxHours = 999;
                        this.answers.forEach(a => {
                            if (a.category) scores[a.category] = (scores[a.category] || 0) + (a.points ?? 0);
                            if (a.hours) maxHours = a.hours;
                        });
                        let topCategory = Object.keys(scores)[0] || null;
                        Object.keys(scores).forEach(cat => {
                            if (scores[cat] > (scores[topCategory] || 0)) topCategory = cat;
                        });
                        let candidates = this.trainings.filter(t => t.category === topCategory);
                        if (candidates.length === 0) candidates = this.trainings;
                        let fitting = candidates.filter(t => t.hours <= maxHours);
                        let pool = fitting.length ? fitting : candidates;
                        this.recommendation = pool.reduce((shortest, t) => (t.hours < shortest.hours ? t : shortest), pool[0]);
                        this.finished = true;

                        fetch('{{ route('training-needs-assessment.recommendation') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                training_slug: this.recommendation.slug,
                                answers: this.answers,
                                category_scores: scores,
                                top_category: topCategory,
                                max_hours: maxHours,
                            }),
                        }).catch(() => {});
                    },
                }"
            >
                <!-- Progress -->
                <div class="mb-8" x-show="!finished">
                    <p class="text-sm font-semibold text-[#152A4E] dark:text-white mb-2">
                        {{ __('Question') }} <span x-text="step + 1"></span> {{ __('of') }} <span x-text="questions.length"></span>
                    </p>
                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#152A4E] to-[#E2762D] transition-all duration-300"
                            :style="'width: ' + ((step + (finished ? 1 : 0)) / questions.length * 100) + '%'"></div>
                    </div>
                </div>

                <!-- Question -->
                <div x-show="!finished" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <p x-show="current.group_label" x-cloak class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2" x-text="current.group_label"></p>
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white" x-text="current.question"></h2>
                    <p x-show="current.help_text" x-cloak class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="current.help_text"></p>

                    <div class="mt-6 space-y-2">
                        <!-- Pick one -->
                        <template x-if="current.kind === 'single_choice'">
                            <div class="space-y-2">
                                <template x-for="option in current.options" :key="option.label">
                                    <button type="button" @click="choose(option)"
                                        class="w-full text-left rounded-lg border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:border-[#152A4E] dark:hover:border-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        x-text="option.label"></button>
                                </template>
                            </div>
                        </template>

                        <!-- Pick any that apply -->
                        <template x-if="current.kind === 'multi_choice'">
                            <div class="space-y-2">
                                <template x-for="option in current.options" :key="option.label">
                                    <button type="button" @click="toggle(option)"
                                        :class="isPicked(option)
                                            ? 'border-[#152A4E] dark:border-white bg-[#152A4E]/5 dark:bg-white/10'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-[#152A4E] dark:hover:border-white'"
                                        class="w-full flex items-start gap-3 text-left rounded-lg border px-4 py-3 text-sm text-gray-700 dark:text-gray-200 transition">
                                        <span :class="isPicked(option) ? 'bg-[#152A4E] dark:bg-white border-[#152A4E] dark:border-white' : 'border-gray-300 dark:border-gray-500'"
                                            class="mt-0.5 shrink-0 w-4 h-4 rounded border flex items-center justify-center">
                                            <svg x-show="isPicked(option)" x-cloak class="w-3 h-3 text-white dark:text-[#152A4E]" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </span>
                                        <span x-text="option.label"></span>
                                    </button>
                                </template>
                                <button type="button" @click="submitChecklist()" :disabled="!canContinue"
                                    class="mt-3 w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] disabled:opacity-40 transition">
                                    {{ __('Continue') }}
                                </button>
                            </div>
                        </template>

                        <!-- Rate competency and importance -->
                        <template x-if="current.kind === 'competency'">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Competency') }}</p>
                                    <div class="flex gap-1.5">
                                        <template x-for="value in [1,2,3,4,5]" :key="'c' + value">
                                            <button type="button" @click="competency = value"
                                                :class="competency === value ? 'bg-[#152A4E] text-white border-[#152A4E]' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#152A4E]'"
                                                class="w-10 h-10 rounded-lg border text-sm font-semibold transition" x-text="value"></button>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __('Importance') }}</p>
                                    <div class="flex gap-1.5">
                                        <template x-for="value in [1,2,3,4,5]" :key="'i' + value">
                                            <button type="button" @click="importance = value"
                                                :class="importance === value ? 'bg-[#E2762D] text-white border-[#E2762D]' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-[#E2762D]'"
                                                class="w-10 h-10 rounded-lg border text-sm font-semibold transition" x-text="value"></button>
                                        </template>
                                    </div>
                                </div>
                                <button type="button" @click="submitCompetency()" :disabled="!canContinue"
                                    class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] disabled:opacity-40 transition">
                                    {{ __('Continue') }}
                                </button>
                            </div>
                        </template>

                        <!-- Short answer / number -->
                        <template x-if="current.kind === 'short_text' || current.kind === 'number'">
                            <div class="space-y-3">
                                <input :type="current.kind === 'number' ? 'number' : 'text'" x-model="text" min="0"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#152A4E] focus:ring-[#152A4E]">
                                <button type="button" @click="submitText()" :disabled="!canContinue"
                                    class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] disabled:opacity-40 transition">
                                    {{ __('Continue') }}
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-between gap-3 mt-6">
                        <button type="button" @click="back()" x-show="step > 0"
                            class="text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition">
                            {{ __('Back') }}
                        </button>
                        <button type="button" @click="skip()" x-show="!current.is_required"
                            class="ms-auto text-sm font-semibold text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition">
                            {{ __('Skip') }}
                        </button>
                    </div>
                </div>

                <!-- Recommendation -->
                <div x-show="finished" x-cloak class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-[#152A4E]/10 dark:bg-white/10 mb-4">
                        <svg class="w-7 h-7 text-[#152A4E] dark:text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('We suggest this training for you') }}</p>
                    <h2 class="text-xl font-bold text-[#152A4E] dark:text-white mt-1" x-text="recommendation?.title"></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2" x-text="recommendation?.description"></p>

                    <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                        <a :href="'{{ url('trainings') }}/' + recommendation?.slug"
                            class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('View this training') }}
                        </a>
                        <button type="button" @click="restart()"
                            class="text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition">
                            {{ __('Start over') }}
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
