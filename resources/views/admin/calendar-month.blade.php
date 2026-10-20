<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Calendar') }}
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

            @include('admin.partials.calendar-header', [
                'countLabel' => trans_choice(
                    ':count training in :region|:count trainings in :region',
                    $monthCount,
                    ['count' => $monthCount, 'region' => $region],
                ),
                'legendHint' => __('A bar runs across every day the training is on.'),
            ])

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                {{-- Sunday-first, like the wall calendars the regions work from. --}}
                <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $index => $weekday)
                        <div class="px-2 py-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 {{ $index < 6 ? 'border-e border-gray-200 dark:border-gray-700' : '' }}">
                            {{ __($weekday) }}
                        </div>
                    @endforeach
                </div>

                @foreach ($weeks as $weekIndex => $week)
                    {{-- One CSS grid per week. Row 1 holds the day numbers and the
                         rows under it are the bar lanes, so a training that runs
                         Monday to Thursday is a single element spanning four
                         columns rather than four separate chips. --}}
                    <div class="grid grid-cols-7 {{ $weekIndex < count($weeks) - 1 ? 'border-b border-gray-200 dark:border-gray-700' : '' }}"
                        style="grid-template-rows: auto repeat({{ $week['lanes'] }}, auto);">

                        {{-- Cell backgrounds first, each spanning the full height of
                             the week, so the bars drawn afterwards sit on top of them
                             without needing to be positioned. --}}
                        @foreach ($week['days'] as $dayIndex => $day)
                            <div style="grid-column: {{ $dayIndex + 1 }}; grid-row: 1 / -1;"
                                class="min-h-[7rem] {{ $dayIndex < 6 ? 'border-e border-gray-200 dark:border-gray-700' : '' }} {{ $day['inMonth'] ? ($day['isWeekend'] ? 'bg-gray-50/60 dark:bg-gray-900/20' : '') : 'bg-gray-100/70 dark:bg-gray-900/50' }}"></div>
                        @endforeach

                        @foreach ($week['days'] as $dayIndex => $day)
                            <div style="grid-column: {{ $dayIndex + 1 }}; grid-row: 1;" class="px-2 pt-1.5 pb-1 text-end">
                                <span @class([
                                    'inline-flex items-center justify-center text-xs tabular-nums',
                                    'h-6 min-w-6 px-1.5 rounded-full bg-[#152A4E] text-white font-bold' => $day['isToday'],
                                    'font-medium text-gray-700 dark:text-gray-200' => ! $day['isToday'] && $day['inMonth'],
                                    'text-gray-400 dark:text-gray-600' => ! $day['isToday'] && ! $day['inMonth'],
                                ])>{{ $day['date']->day }}</span>
                            </div>
                        @endforeach

                        @foreach ($week['segments'] as $segment)
                            @php $training = $segment['training']; @endphp
                            <a href="{{ route('admin.summary.edit', $training) }}"
                                style="grid-column: {{ $segment['startColumn'] }} / span {{ $segment['span'] }}; grid-row: {{ $segment['lane'] + 2 }};"
                                title="{{ $training->training_title }} · {{ $training->dateRangeLabel() }} · {{ $training->venue ?: __('Venue TBD') }}"
                                @class([
                                    'relative z-10 flex items-center gap-1 mb-1 px-1.5 py-1 text-[11px] font-semibold leading-tight truncate transition',
                                    $categoryBars[$training->category] ?? 'bg-gray-500 hover:bg-gray-600 text-white',
                                    // A run that carries on past this week keeps its
                                    // squared edge against the week boundary; a run
                                    // that starts or ends here gets the rounded cap
                                    // and the margin that separates it from the next
                                    // day's bar.
                                    'ms-1 rounded-s' => ! $segment['continuesBefore'],
                                    'me-1 rounded-e' => ! $segment['continuesAfter'],
                                ])>
                                @if ($segment['continuesBefore'])
                                    <svg class="w-3 h-3 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                    </svg>
                                @endif
                                <span class="truncate">{{ $training->training_title }}</span>
                                @if ($segment['continuesAfter'])
                                    <svg class="w-3 h-3 shrink-0 ms-auto opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if ($monthCount === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $search !== ''
                        ? __('Nothing in :month matches ":search".', ['month' => $month->format('F Y'), 'search' => $search])
                        : __('Nothing scheduled in :month yet.', ['month' => $month->format('F Y')]) }}
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
