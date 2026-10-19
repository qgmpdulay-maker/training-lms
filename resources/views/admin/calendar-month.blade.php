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

            {{-- The month reads as the page's title, the way it does on a wall
                 calendar; the row under it only says which slice is in view. --}}
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h3 class="text-2xl font-bold text-[#152A4E] dark:text-white leading-tight">{{ $month->format('F Y') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ trans_choice(':count training in :region|:count trainings in :region', $monthCount, ['count' => $monthCount, 'region' => $region]) }}
                </p>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center gap-3 -mt-2">
                <div class="flex items-center gap-2">
                    @php
                        $monthLink = fn (?string $value) => route('admin.calendar', array_filter([
                            'month' => $value,
                            // Carried so paging a month doesn't drop Central
                            // back to the all-regions agenda, or lose a search.
                            'region' => $regionPicker ? $region : null,
                            'search' => $search !== '' ? $search : null,
                        ]));
                    @endphp

                    <a href="{{ $monthLink($previousMonth) }}" aria-label="{{ __('Previous month') }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-[#152A4E] hover:text-[#152A4E] dark:hover:border-white dark:hover:text-white transition">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                    <a href="{{ $monthLink($nextMonth) }}" aria-label="{{ __('Next month') }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-[#152A4E] hover:text-[#152A4E] dark:hover:border-white dark:hover:text-white transition">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    <a href="{{ $monthLink(null) }}"
                        class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 px-3 h-9 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:border-[#152A4E] hover:text-[#152A4E] dark:hover:border-white dark:hover:text-white transition">
                        {{ __('Today') }}
                    </a>

                    {{-- Jump straight to any month. The arrows are for stepping
                         to next week's training; reaching last year through them
                         is twenty-odd clicks. The two selects carry no name, so
                         nothing but the composed `month` is ever submitted and
                         the URL stays the same shape the arrows produce. --}}
                    <form method="GET" action="{{ route('admin.calendar') }}" class="flex items-center gap-2">
                        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                        @if ($regionPicker)
                            <input type="hidden" name="region" value="{{ $region }}">
                        @endif
                        @if ($search !== '')
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif

                        <select aria-label="{{ __('Month') }}" onchange="jumpToMonth(this.form)"
                            class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm py-1.5 ps-3 pe-8 h-9">
                            @foreach (range(1, 12) as $monthNumber)
                                <option value="{{ str_pad($monthNumber, 2, '0', STR_PAD_LEFT) }}" @selected($month->month === $monthNumber)>
                                    {{ \Carbon\Carbon::create(null, $monthNumber, 1)->format('F') }}
                                </option>
                            @endforeach
                        </select>

                        <select aria-label="{{ __('Year') }}" onchange="jumpToMonth(this.form)"
                            class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm py-1.5 ps-3 pe-8 h-9">
                            @foreach ($selectableYears as $year)
                                <option value="{{ $year }}" @selected($month->year === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <form method="GET" action="{{ route('admin.calendar') }}" class="lg:ms-auto flex items-center gap-2 shrink-0">
                    {{-- The month rides along so searching doesn't throw you back
                         to today's month mid-browse. --}}
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    @if ($regionPicker)
                        <input type="hidden" name="region" value="{{ $region }}">
                    @endif
                    <div class="relative">
                        <x-input-label for="search" :value="__('Search')" class="sr-only" />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input id="search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('Search trainings or agencies…') }}"
                            class="w-full sm:w-64 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm ps-9 py-2 transition">
                    </div>
                    @if ($search !== '')
                        <a href="{{ route('admin.calendar', array_filter(['month' => $month->format('Y-m'), 'region' => $regionPicker ? $region : null])) }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </form>

                @if ($regionPicker)
                    {{-- Central's way between regions, and back out to the
                         all-regions agenda, which is what an empty value gives. --}}
                    <form method="GET" action="{{ route('admin.calendar') }}" class="flex items-center gap-2">
                        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                        @if ($search !== '')
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        <select name="region" aria-label="{{ __('Region') }}" onchange="this.form.submit()"
                            class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:border-[#152A4E] focus:ring-[#152A4E] text-sm py-2 ps-3 pe-8">
                            <option value="">{{ __('All Regions (agenda)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if ($canSchedule)
                    <a href="{{ route('admin.trainings.create') }}"
                        class="inline-flex items-center justify-center gap-2 bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('Schedule a Training') }}
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-4 flex-wrap text-xs font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-orange-500"></span>{{ __('TA') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-600"></span>{{ __('APB') }}</span>
                <span class="text-gray-400 dark:text-gray-500">{{ __('A bar runs across every day the training is on.') }}</span>
            </div>

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

            <script>
                // The month and year selects are deliberately nameless — this
                // folds them into the single `month` parameter the arrows and
                // every shared link already use.
                function jumpToMonth(form) {
                    const [monthSelect, yearSelect] = form.querySelectorAll('select');
                    form.month.value = `${yearSelect.value}-${monthSelect.value}`;
                    form.submit();
                }
            </script>

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
