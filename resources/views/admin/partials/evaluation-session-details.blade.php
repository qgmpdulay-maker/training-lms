{{--
    Full L1/L2 evaluation results for ONE training session, shown when its row
    is expanded on the Tools page (Evaluation Computation section).

    Not rendered with the Tools page itself: the browser downloads this fragment
    from ToolsController::evaluationDetails() the first time the row is opened.
    $session is built by ToolsController::sessionSummary().

    The results are split into tabs; a tab only appears when there is data for it.
--}}
@php
    // Which tabs have data to show for this session.
    $hasL1 = $session['modules']->isNotEmpty();
    $hasDistribution = $hasL1 && $session['modules']->contains(fn ($module) => $module['participant_responses'] > 0);
    $hasTrainerSummary = $hasL1 && $session['trainer_ratings_by_module']->isNotEmpty();
    $hasTrainerRatings = $hasL1 && $session['instructor_ratings']->isNotEmpty();
    $hasPerTaker = ! empty($session['module_matrix_columns']);
    $hasL2 = $session['pretest_stats']['count'] > 0 || $session['posttest_stats']['count'] > 0;

    // Tab key => label shown on the tab button; tabs without data are dropped.
    $evalTabs = collect([
        'l1' => ['label' => 'Module & Trainer Ratings', 'show' => $hasL1],
        'distribution' => ['label' => 'Level 1 Reaction Evaluation Report', 'show' => $hasDistribution],
        'trainerSummary' => ['label' => "Trainer's Rating Summary", 'show' => $hasTrainerSummary],
        'trainerRatings' => ['label' => 'Participant Trainer Ratings', 'show' => $hasTrainerRatings],
        'perTaker' => ['label' => 'Per-Taker Scores', 'show' => $hasPerTaker],
        'l2' => ['label' => 'Level 2 Learning Evaluation Report', 'show' => $hasL2],
    ])->filter(fn ($tab) => $tab['show']);
@endphp

@if ($evalTabs->isEmpty())
    <p class="px-6 pb-6 pt-1 text-sm text-gray-400">{{ __('No evaluation data recorded for this session yet.') }}</p>
@else
    {{-- Tab bar. activeEvalTab is the tab currently shown (starts on the first available one). --}}
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

        {{-- Tab: average module and trainer ratings — admin-entered values beside the participants' average. --}}
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

        {{-- Tab: how many participants gave each 1–5 rating per module, with their anonymous comments. --}}
        @if ($hasDistribution)
            <div x-show="activeEvalTab === 'distribution'" x-cloak>
                <div class="max-h-[28rem] overflow-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-white dark:bg-gray-800">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-3 pl-4 pr-4">{{ __('Modules') }}</th>
                                @foreach (range(1, 5) as $value)
                                    <th class="py-3 pr-4 text-center">{{ $value }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($session['modules'] as $module)
                                <tr>
                                    <td class="py-3 pl-4 pr-4 text-gray-700 dark:text-gray-200">
                                        {{ $module['module'] }}
                                        <span class="text-gray-400">({{ __('Average Rating: :rating', ['rating' => $module['participant_rating'] ?? '—']) }})</span>
                                    </td>
                                    @foreach (range(1, 5) as $value)
                                        <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">
                                            {{ $module['rating_distribution'][$value] }}
                                            @if ($module['participant_responses'] > 0)
                                                <span class="text-gray-400 text-xs">({{ round($module['rating_distribution'][$value] / $module['participant_responses'] * 100, 1) }}%)</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @if (! empty($module['comments']))
                                    <tr>
                                        <td colspan="6" class="py-3 pl-4 pr-4 bg-gray-50/60 dark:bg-gray-900/20">
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
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                    {{ __('Legend: 1 – Poor, 2 – Unsatisfactory, 3 – Satisfactory, 4 – Very Satisfactory, 5 – Outstanding') }}
                </p>
            </div>
        @endif

        {{-- Tab: trainer rating per module, pooling admin and participant ratings, with the 1–5 breakdown. --}}
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
                                    <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-300 tabular-nums">
                                        {{ $moduleTrainerRating['rating_distribution'][$value] }}
                                        @if ($moduleTrainerRating['responses'] > 0)
                                            <span class="text-gray-400 text-xs">({{ round($moduleTrainerRating['rating_distribution'][$value] / $moduleTrainerRating['responses'] * 100, 1) }}%)</span>
                                        @endif
                                    </td>
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

        {{-- Tab: each instructor's overall rating from participants, with the 1–5 breakdown and comments. --}}
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

        {{-- Tab: one row per participant showing the module and trainer rating they gave for every module. --}}
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

        {{-- Tab: pre-test vs post-test score statistics (mean, median, mode, lowest, highest, number of takers). --}}
        @if ($hasL2)
            <div x-show="activeEvalTab === 'l2'" x-cloak class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-3 pl-4 pr-4"></th>
                            <th class="py-3 pr-4">{{ __('Pre-Test') }}</th>
                            <th class="py-3 pr-4">{{ __('Post-Test') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ([
                            'Mean' => 'mean',
                            'Median' => 'median',
                            'Mode' => 'mode',
                            'Lowest Score (Minimum)' => 'min',
                            'Highest Score (Maximum)' => 'max',
                            'Number of Takers (Count)' => 'count',
                        ] as $label => $key)
                            <tr>
                                <td class="py-3 pl-4 pr-4 font-medium text-[#152A4E] dark:text-white">{{ __($label) }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $session['pretest_stats'][$key] ?? '—' }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $session['posttest_stats'][$key] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
