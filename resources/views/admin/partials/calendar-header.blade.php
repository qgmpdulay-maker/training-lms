{{-- The chrome both calendars wear: the month as the page's title, one row of
     controls, and the category legend.

     Shared so the two views cannot drift apart again. Central's all-regions
     agenda and a single region's month grid are different shapes of the same
     page, and they used to announce themselves with completely different
     furniture — a navy "Filter" card and a pill tab strip on one, a flat
     title and bordered controls on the other.

     Expects: $month, $previousMonth, $nextMonth, $selectableYears, $search,
     $regions, $region (null on the all-regions agenda), $regionPicker,
     $canSchedule, $countLabel. --}}

@php
    // A Regional Admin's region is implied by who they are, so it never needs
    // to appear in a URL; Central's chosen region does, or paging a month
    // would drop them back to the all-regions agenda. Null on that agenda.
    $linkedRegion = $regionPicker ? $region : null;
@endphp

<div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
    <h3 class="text-2xl font-bold text-[#152A4E] dark:text-white leading-tight">{{ $month->format('F Y') }}</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $countLabel }}</p>
</div>

{{-- Wraps rather than overflowing: with the region picker in the row, the
     controls are wider than a 1024px window, and an off-screen control is
     worse than a second line. --}}
<div class="flex flex-col lg:flex-row lg:flex-wrap lg:items-center gap-3 -mt-2">
    <div class="flex items-center gap-2">
        @php
            $monthLink = fn (?string $value) => route('admin.calendar', array_filter([
                'month' => $value,
                // Carried so paging a month doesn't drop Central back to the
                // all-regions agenda, or lose a search.
                'region' => $linkedRegion,
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

        {{-- Jump straight to any month. The arrows are for stepping to next
             week's training; reaching last year through them is twenty-odd
             clicks. The two selects carry no name, so nothing but the composed
             `month` is ever submitted and the URL stays the same shape the
             arrows produce. --}}
        <form method="GET" action="{{ route('admin.calendar') }}" class="flex items-center gap-2">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            @if ($linkedRegion)
                <input type="hidden" name="region" value="{{ $linkedRegion }}">
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
        {{-- The month rides along so searching doesn't throw you back to
             today's month mid-browse. --}}
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
        @if ($linkedRegion)
            <input type="hidden" name="region" value="{{ $linkedRegion }}">
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
            <a href="{{ route('admin.calendar', array_filter(['month' => $month->format('Y-m'), 'region' => $linkedRegion])) }}"
                class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                {{ __('Clear') }}
            </a>
        @endif
    </form>

    @if ($regionPicker)
        {{-- Central's way between regions, and back out to the all-regions
             agenda, which is what an empty value gives. --}}
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
</div>

<script>
    // The month and year selects are deliberately nameless — this folds them
    // into the single `month` parameter the arrows and every shared link
    // already use.
    function jumpToMonth(form) {
        const [monthSelect, yearSelect] = form.querySelectorAll('select');
        form.month.value = `${yearSelect.value}-${monthSelect.value}`;
        form.submit();
    }
</script>
