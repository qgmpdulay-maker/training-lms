<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tools') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($regionLocked)
                <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('Showing :region only. Requests need to be tagged with a region on the Summary tab to show up here.', ['region' => $region]) }}</span>
                </div>
            @else
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.tools') }}" class="flex items-center gap-2">
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($region)
                        <a href="{{ route('admin.tools') }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <!-- ATAR -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('ATAR') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Blank template, then upload each completed ATAR below.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.tools.atar-template') }}" target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-full border border-gray-200 dark:border-gray-600 text-[#152A4E] dark:text-white px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                            </svg>
                            {{ __('ATAR Template') }}
                        </a>
                    </div>
                </div>

                <div class="mb-5">
                    <form data-live-form data-live-section="files" data-live-target="files-section"
                        method="GET" action="{{ route('admin.tools') }}#files-section" class="w-full">
                        <input type="hidden" name="_section" value="files">
                        @if ($region)
                            <input type="hidden" name="region" value="{{ $region }}">
                        @endif
                        <div class="flex items-stretch sm:items-center gap-2 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700 p-2">
                            <div class="relative flex-1">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                <input type="text" name="files_q" value="{{ $filesSearch }}" placeholder="{{ __('Search training, venue, LGU, or participant…') }}"
                                    class="w-full rounded-xl border-0 bg-white dark:bg-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-[#152A4E]/15 text-sm pl-10 py-2.5 transition">
                            </div>
                            <button type="submit"
                                class="shrink-0 inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                                {{ __('Search') }}
                            </button>
                        </div>
                    </form>
                    @if ($filesSearch !== '')
                        <a href="{{ route('admin.tools', $region ? ['region' => $region] : []) }}#files-section"
                            class="inline-block mt-2 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset search') }}
                        </a>
                    @endif
                </div>

                <div id="files-section">
                    @include('admin.partials.files-table')
                </div>
            </div>

            <!-- Evaluation Computation (L1 / L2) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Evaluation Computation (L1 / L2)') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Combines the admin-entered evaluation with what participants submitted themselves. Pick a training below, then expand a session to see its L1 and L2 results.') }}
                </p>

                @if (empty($evaluationsByTraining))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No evaluations entered yet.') }}
                    </div>
                @else
                    <div x-data="{ activeTraining: @js(array_key_first($evaluationsByTraining)) }">
                        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                            @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                                <button type="button" @click="activeTraining = @js($trainingTitle)"
                                    :class="activeTraining === @js($trainingTitle)
                                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                    {{ $trainingTitle }}
                                    <span :class="activeTraining === @js($trainingTitle)
                                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                        {{ $sessions->count() }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                            <div x-show="activeTraining === @js($trainingTitle)" x-cloak class="mt-5">
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($sessions as $session)
                                        <div x-data="{ open: false }">
                                            <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $session['venue'] }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $session['preferred_date']->format('M j, Y') }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0">
                                                    @if ($session['participant_total'] > 0)
                                                        <span class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400" title="{{ __('Participants who submitted their own evaluation') }}">
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                                            {{ $session['participant_response_count'] }}/{{ $session['participant_total'] }} {{ __('evaluated') }}
                                                        </span>
                                                    @endif
                                                    @if ($session['overall_trainer_rating'])
                                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.958c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.958a1 1 0 00-.364-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z" /></svg>
                                                            {{ $session['overall_trainer_rating'] }}
                                                        </span>
                                                    @endif
                                                    <span class="hidden sm:inline text-xs text-gray-400" title="{{ $session['updated_at']?->format('M j, Y g:i A') }}">
                                                        {{ __('Updated') }} {{ $session['updated_at']?->diffForHumans() ?? '—' }}
                                                    </span>
                                                </div>
                                            </button>

                                            <div x-show="open" x-cloak class="bg-gray-50/60 dark:bg-gray-900/20">
                                                @php
                                                    $hasL1 = $session['modules']->isNotEmpty();
                                                    $hasDistribution = $hasL1 && $session['modules']->contains(fn ($module) => $module['participant_responses'] > 0);
                                                    $hasTrainerSummary = $hasL1 && $session['trainer_ratings_by_module']->isNotEmpty();
                                                    $hasTrainerRatings = $hasL1 && $session['instructor_ratings']->isNotEmpty();
                                                    $hasPerTaker = ! empty($session['module_matrix_columns']);
                                                    $hasL2 = $session['pretest_stats']['count'] > 0 || $session['posttest_stats']['count'] > 0;

                                                    $evalTabs = collect([
                                                        'l1' => ['label' => 'Module & Trainer Ratings', 'show' => $hasL1],
                                                        'distribution' => ['label' => 'Rating Distribution', 'show' => $hasDistribution],
                                                        'trainerSummary' => ['label' => "Trainer's Rating Summary", 'show' => $hasTrainerSummary],
                                                        'trainerRatings' => ['label' => 'Participant Trainer Ratings', 'show' => $hasTrainerRatings],
                                                        'perTaker' => ['label' => 'Per-Taker Scores', 'show' => $hasPerTaker],
                                                        'l2' => ['label' => 'Pre/Post Test (L2)', 'show' => $hasL2],
                                                    ])->filter(fn ($tab) => $tab['show']);
                                                @endphp

                                                @if ($evalTabs->isEmpty())
                                                    <p class="px-6 pb-6 pt-1 text-sm text-gray-400">{{ __('No evaluation data recorded for this session yet.') }}</p>
                                                @else
                                                    <div x-data="{ activeEvalTab: @js($evalTabs->keys()->first()) }" class="px-6 pb-6 pt-1">
                                                        <div class="flex items-center gap-1 overflow-x-auto border-b border-gray-200 dark:border-gray-700 mb-5">
                                                            @foreach ($evalTabs as $key => $tab)
                                                                <button type="button" @click="activeEvalTab = @js($key)"
                                                                    :class="activeEvalTab === @js($key)
                                                                        ? 'text-[#152A4E] dark:text-white border-[#152A4E] dark:border-white'
                                                                        : 'text-gray-400 dark:text-gray-500 border-transparent hover:text-gray-600 dark:hover:text-gray-300'"
                                                                    class="shrink-0 border-b-2 px-3 py-2.5 text-sm font-semibold transition whitespace-nowrap">
                                                                    {{ __($tab['label']) }}
                                                                </button>
                                                            @endforeach
                                                        </div>

                                                        @if ($hasL1)
                                                            <div x-show="activeEvalTab === 'l1'" x-cloak>
                                                                <div class="max-h-[28rem] overflow-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                    <table class="min-w-full text-sm">
                                                                        <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                                                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                                <th class="py-3 pl-4 pr-4">{{ __('Module') }}</th>
                                                                                <th class="py-3 pr-4">{{ __('Module Rating') }}</th>
                                                                                <th class="py-3 pr-4">{{ __('Trainer Rating') }}</th>
                                                                                <th class="py-3 pr-4">{{ __('Participant Avg') }}</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                            @foreach ($session['modules'] as $module)
                                                                                <tr>
                                                                                    <td class="py-3 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $module['module'] }}</td>
                                                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $module['module_rating'] ?? '—' }}</td>
                                                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $module['trainer_rating'] ?? '—' }}</td>
                                                                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                                                                        {{ $module['participant_rating'] ?? '—' }}
                                                                                        @if ($module['participant_responses'] > 0)
                                                                                            <span class="text-gray-400">({{ trans_choice(':count response|:count responses', $module['participant_responses'], ['count' => $module['participant_responses']]) }})</span>
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                                                                    {{ __('Overall Trainer Rating:') }} <span class="font-semibold text-[#152A4E] dark:text-white">{{ $session['overall_trainer_rating'] ?? '—' }}</span>
                                                                    {{ __('(reflected on the Instructors tab when exactly one instructor teaches this training)') }}
                                                                </p>
                                                            </div>
                                                        @endif

                                                        @if ($hasDistribution)
                                                            <div x-show="activeEvalTab === 'distribution'" x-cloak class="max-h-[28rem] overflow-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-3 pl-4 pr-4">{{ __('Module') }}</th>
                                                                            @foreach (range(1, 5) as $value)
                                                                                <th class="py-3 pr-4 text-center">{{ $value }}</th>
                                                                            @endforeach
                                                                            <th class="py-3 pr-4">{{ __('Responses') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach ($session['modules'] as $module)
                                                                            <tr>
                                                                                <td class="py-3 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $module['module'] }}</td>
                                                                                @foreach (range(1, 5) as $value)
                                                                                    <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $module['rating_distribution'][$value] }}</td>
                                                                                @endforeach
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $module['participant_responses'] }}</td>
                                                                            </tr>
                                                                            @if (! empty($module['comments']))
                                                                                <tr>
                                                                                    <td colspan="7" class="py-3 pl-4 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
                                                                                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __(':module — Comments (anonymous)', ['module' => $module['module']]) }}</p>
                                                                                        <ul class="space-y-1.5">
                                                                                            @foreach ($module['comments'] as $comment)
                                                                                                <li class="text-sm text-gray-600 dark:text-gray-300">"{{ $comment }}"</li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif

                                                        @if ($hasTrainerSummary)
                                                            <div x-show="activeEvalTab === 'trainerSummary'" x-cloak class="max-h-[28rem] overflow-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-3 pl-4 pr-4">{{ __('Module') }}</th>
                                                                            @foreach (range(1, 5) as $value)
                                                                                <th class="py-3 pr-4 text-center">{{ $value }}</th>
                                                                            @endforeach
                                                                            <th class="py-3 pr-4">{{ __('Avg') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Trainer Name') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Organization / Agency') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach ($session['trainer_ratings_by_module'] as $moduleTrainerRating)
                                                                            <tr>
                                                                                <td class="py-3 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $moduleTrainerRating['module'] }}</td>
                                                                                @foreach (range(1, 5) as $value)
                                                                                    <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $moduleTrainerRating['rating_distribution'][$value] }}</td>
                                                                                @endforeach
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $moduleTrainerRating['rating'] }}</td>
                                                                                <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $moduleTrainerRating['trainer'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $moduleTrainerRating['organization'] ?? '—' }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif

                                                        @if ($hasTrainerRatings)
                                                            <div x-show="activeEvalTab === 'trainerRatings'" x-cloak class="max-h-[28rem] overflow-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-3 pl-4 pr-4">{{ __('Trainer') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Organization') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Avg') }}</th>
                                                                            @foreach (range(1, 5) as $value)
                                                                                <th class="py-3 pr-4 text-center">{{ $value }}</th>
                                                                            @endforeach
                                                                            <th class="py-3 pr-4">{{ __('Responses') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach ($session['instructor_ratings'] as $instructorRating)
                                                                            <tr>
                                                                                <td class="py-3 pl-4 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $instructorRating['instructor'] }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructorRating['agency_organization'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $instructorRating['rating'] }}</td>
                                                                                @foreach (range(1, 5) as $value)
                                                                                    <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $instructorRating['rating_distribution'][$value] }}</td>
                                                                                @endforeach
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $instructorRating['responses'] }}</td>
                                                                            </tr>
                                                                            @if (! empty($instructorRating['comments']))
                                                                                <tr>
                                                                                    <td colspan="8" class="py-3 pl-4 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
                                                                                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1.5">{{ __(':trainer — Comments (anonymous)', ['trainer' => $instructorRating['instructor']]) }}</p>
                                                                                        <ul class="space-y-1.5">
                                                                                            @foreach ($instructorRating['comments'] as $comment)
                                                                                                <li class="text-sm text-gray-600 dark:text-gray-300">"{{ $comment }}"</li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif

                                                        @if ($hasPerTaker)
                                                            <div x-show="activeEvalTab === 'perTaker'" x-cloak>
                                                                <p class="text-xs text-gray-400 mb-2 sm:hidden">{{ __('Scroll to see every module — the taker column stays put.') }}</p>
                                                                <div class="max-h-[28rem] overflow-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                    <table class="min-w-full text-sm">
                                                                        <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                                                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                                <th class="sticky left-0 z-20 bg-white dark:bg-gray-800 py-3 pl-4 pr-4" rowspan="2">{{ __('Taker') }}</th>
                                                                                @foreach ($session['module_matrix_columns'] as $moduleName)
                                                                                    <th class="py-3 pr-4 text-center" colspan="2">{{ $moduleName }}</th>
                                                                                @endforeach
                                                                                <th class="py-3 pr-4" rowspan="2">{{ __('Overall') }}</th>
                                                                            </tr>
                                                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                                                                @foreach ($session['module_matrix_columns'] as $moduleName)
                                                                                    <th class="py-2 pr-2 text-center font-normal">{{ __('Module') }}</th>
                                                                                    <th class="py-2 pr-4 text-center font-normal">{{ __('Trainer') }}</th>
                                                                                @endforeach
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                            @foreach ($session['module_matrix'] as $takerRow)
                                                                                <tr>
                                                                                    <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 py-3 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $takerRow['participant'] }}</td>
                                                                                    @foreach ($session['module_matrix_columns'] as $moduleName)
                                                                                        <td class="py-3 pr-2 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $takerRow['scores'][$moduleName]['module_rating'] ?? '—' }}</td>
                                                                                        <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">{{ $takerRow['scores'][$moduleName]['trainer_rating'] ?? '—' }}</td>
                                                                                    @endforeach
                                                                                    <td class="py-3 pr-4 font-semibold text-[#152A4E] dark:text-white tabular-nums">{{ $takerRow['overall'] ?? '—' }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                </table>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if ($hasL2)
                                                            <div x-show="activeEvalTab === 'l2'" x-cloak class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                                <table class="min-w-full text-sm">
                                                                    <thead>
                                                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                            <th class="py-3 pl-4 pr-4"></th>
                                                                            <th class="py-3 pr-4">{{ __('Mean') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Median') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Mode') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Min') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Max') }}</th>
                                                                            <th class="py-3 pr-4">{{ __('Count') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                        @foreach (['Pre-Test' => $session['pretest_stats'], 'Post-Test' => $session['posttest_stats']] as $label => $stats)
                                                                            <tr>
                                                                                <td class="py-3 pl-4 pr-4 font-medium text-[#152A4E] dark:text-white">{{ __($label) }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['mean'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['median'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['mode'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['min'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['max'] ?? '—' }}</td>
                                                                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $stats['count'] }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    @include('admin.partials.live-search-script')
</x-app-layout>
