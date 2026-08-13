@php
    // Placeholder scoring. TODO: replace with the real Training Needs
    // Assessment questions/logic once CDTI provides them — the category
    // strings below must keep matching config/trainings.php categories.
    $questions = [
        [
            'question' => 'What is your primary role or responsibility?',
            'options' => [
                ['label' => 'I respond directly during emergencies (rescue, relief operations)', 'category' => 'Emergency Response', 'points' => 2],
                ['label' => 'I lead or coordinate at the barangay/community level', 'category' => 'Community Resilience', 'points' => 2],
                ['label' => 'I handle planning, policy, or DRRM programs for my LGU/agency', 'category' => 'DRRM Core', 'points' => 2],
                ['label' => "I support survivors' well-being (health, counseling, welfare)", 'category' => 'Health & Welfare', 'points' => 2],
            ],
        ],
        [
            'question' => 'How would you describe your current knowledge of disaster risk reduction?',
            'options' => [
                ['label' => 'New to this — just starting out', 'category' => 'DRRM Core', 'points' => 2],
                ['label' => 'I have some experience already', 'category' => null, 'points' => 0],
                ['label' => "I'm experienced and want more specialized skills", 'category' => 'Emergency Response', 'points' => 1],
            ],
        ],
        [
            'question' => 'What do you want your team to be ready for?',
            'options' => [
                ['label' => 'Responding quickly when disaster strikes', 'category' => 'Emergency Response', 'points' => 2],
                ['label' => 'Helping our community prepare and organize', 'category' => 'Community Resilience', 'points' => 2],
                ['label' => "Supporting people's well-being after a disaster", 'category' => 'Health & Welfare', 'points' => 2],
                ['label' => 'Planning and managing our disaster programs', 'category' => 'DRRM Core', 'points' => 2],
            ],
        ],
        [
            'question' => 'How many training hours can your team realistically commit to?',
            'options' => [
                ['label' => 'Less than 8 hours', 'hours' => 8],
                ['label' => '8 to 16 hours', 'hours' => 16],
                ['label' => '16 to 24 hours', 'hours' => 24],
                ['label' => 'More than 24 hours', 'hours' => 999],
            ],
        ],
    ];
@endphp
<x-app-layout>
    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white mb-6">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Dashboard') }}
            </a>

            <h1 class="text-2xl font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Needs Assessment') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">{{ __('Answer a few simple questions and we will suggest a training for you.') }}</p>

            <div
                x-data="{
                    questions: {{ Js::from($questions) }},
                    trainings: {{ Js::from($trainings) }},
                    step: 0,
                    answers: [],
                    finished: {{ $existingRecommendation ? 'true' : 'false' }},
                    recommendation: {{ Js::from($existingRecommendation) }},
                    choose(option) {
                        this.answers[this.step] = option;
                        if (this.step < this.questions.length - 1) {
                            this.step++;
                        } else {
                            this.finish();
                        }
                    },
                    back() {
                        if (this.finished) { this.finished = false; return; }
                        if (this.step > 0) this.step--;
                    },
                    restart() {
                        this.step = 0;
                        this.answers = [];
                        this.finished = false;
                        this.recommendation = null;
                    },
                    finish() {
                        const scores = {};
                        let maxHours = 999;
                        this.answers.forEach(a => {
                            if (a.category) scores[a.category] = (scores[a.category] || 0) + a.points;
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
                            body: JSON.stringify({ training_slug: this.recommendation.slug }),
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
                <template x-if="!finished">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-6" x-text="questions[step].question"></h2>

                        <div class="space-y-3">
                            <template x-for="option in questions[step].options" :key="option.label">
                                <button type="button" @click="choose(option)"
                                    class="w-full text-left p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-[#152A4E] hover:bg-[#152A4E]/5 dark:hover:bg-[#152A4E]/20 transition text-base text-gray-700 dark:text-gray-200">
                                    <span x-text="option.label"></span>
                                </button>
                            </template>
                        </div>

                        <button type="button" @click="back()" x-show="step > 0"
                            class="mt-6 text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                            {{ __('Back') }}
                        </button>
                    </div>
                </template>

                <!-- Recommendation -->
                <template x-if="finished">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 text-center">
                        <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-full bg-[#152A4E]/8 dark:bg-[#152A4E]/30">
                            <svg class="w-7 h-7 text-[#152A4E]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ __('Based on your answers, we recommend') }}</p>
                        <h2 class="text-xl font-bold text-[#152A4E] dark:text-white mb-2" x-text="recommendation.title"></h2>
                        <span class="inline-block text-[11px] font-semibold tracking-wide uppercase text-[#152A4E] dark:text-white bg-[#152A4E]/8 dark:bg-[#152A4E]/30 rounded-full px-2.5 py-1 mb-4"
                            x-text="recommendation.category"></span>
                        <p class="text-sm text-gray-600 dark:text-gray-300 max-w-md mx-auto mb-2" x-text="recommendation.description"></p>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                            <span x-text="recommendation.hours"></span> {{ __('training hours') }}
                        </p>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            {{ __('Your OCD Regional Office will review this and schedule you for a training accordingly.') }}
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                            <button type="button" @click="restart()"
                                class="w-full sm:w-auto text-sm font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white px-6 py-3">
                                {{ __('Retake Assessment') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
