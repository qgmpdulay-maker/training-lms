{{--
    One training's evaluation results, read-only. This is where the Summary
    page's Evaluations card lands: the reviews and nothing else — no status,
    no roster, no certificate fields, no Save button anywhere on the page.

    The breakdown itself is the same fragment the Tools page expands inline
    (evaluation-session-details), built by App\Services\EvaluationSummary, so
    both places read identical figures.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluation Summary') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admin.summary') }}#evaluations" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white">
                <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ __('Back to Summary') }}
            </a>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h1 class="text-xl font-bold text-[#152A4E] dark:text-white">{{ $record->training_title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $record->requesting_agency }}
                    @if ($record->region)
                        · {{ $record->region }}
                    @endif
                </p>

                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-5 mt-6">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Date & Venue') }}</dt>
                        <dd class="text-sm text-gray-700 dark:text-gray-200 mt-1">
                            {{ $record->preferred_date->format('M j, Y') }}
                            <div class="text-xs text-gray-400">{{ $record->venue }}</div>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Responses') }}</dt>
                        <dd class="text-sm text-gray-700 dark:text-gray-200 mt-1">
                            {{ __(':count of :total submitted', ['count' => $session['participant_response_count'], 'total' => $session['participant_total']]) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Overall Trainer Rating') }}</dt>
                        <dd class="text-sm text-gray-700 dark:text-gray-200 mt-1 tabular-nums">{{ $session['overall_trainer_rating'] ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- The partial carries its own px-6 pb-6 padding (it was written for
                 the Tools page's expanded row), so the card around it has none. --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm pt-6 overflow-hidden">
                @include('admin.partials.evaluation-session-details')
            </div>
        </div>
    </div>
</x-app-layout>
