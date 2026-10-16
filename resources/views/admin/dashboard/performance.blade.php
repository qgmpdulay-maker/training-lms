{{--
    Regional Performance & Graduates Map section — first thing under the
    welcome banner. Its From / Until / Region / Training fields are the one
    filter for the whole dashboard: every chart, table, and stat card below
    follows them. A Regional Admin sees their own region in place of the
    Region select, since DashboardController::index() ignores any other.
    Every field auto-submits on change instead of needing an "Apply Filters"
    button — see submitDashboardFilter() in index.blade.php.
--}}
@php
    // A Regional Admin's region is always set, so it never counts as a filter
    // to reset.
    $hasFilters = (! $regionLocked && $region) || $filters['training_title'] || $filters['from'] || $filters['until'];
@endphp
<div class="space-y-6">
    <!-- Filter -->
    <x-chart-card body-class="p-6 sm:p-8"
        :title="__('Regional Performance & Graduates Map')">
        @if ($hasFilters)
            <x-slot:action>
                <a href="{{ route('admin.dashboard') }}" onclick="return submitDashboardFilterReset(event)" class="text-xs font-semibold text-white/70 hover:text-white transition whitespace-nowrap">
                    {{ __('Reset') }}
                </a>
            </x-slot:action>
        @endif

        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4" onsubmit="return submitDashboardFilter(this, event)">
            <div>
                <label for="from" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('From') }}</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" onchange="submitDashboardFilter(this)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:[color-scheme:dark] text-sm py-2.5 px-3 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
            </div>
            <div>
                <label for="until" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Until') }}</label>
                <input type="date" id="until" name="until" value="{{ $filters['until'] }}" onchange="submitDashboardFilter(this)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:[color-scheme:dark] text-sm py-2.5 px-3 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
            </div>
            <div class="sm:col-span-2 lg:col-span-1">
                <label @unless ($regionLocked) for="region" @endunless class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Region') }}</label>
                @if ($regionLocked)
                    <div title="{{ __('Locked to your region') }}"
                        class="flex items-center justify-between gap-2 w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-sm text-gray-600 dark:text-gray-300 py-2.5 px-3">
                        <span class="truncate">{{ $region }}</span>
                        <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                @else
                    <div class="relative">
                        <select id="region" name="region" onchange="submitDashboardFilter(this)"
                            class="appearance-none w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5 pl-3 pr-9 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                            <option value="" @selected(! $region)>{{ __('All Regions') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                @endif
            </div>
            <div class="sm:col-span-2">
                <label for="training_title" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Training') }}</label>
                <div class="relative">
                    <select id="training_title" name="training_title" onchange="submitDashboardFilter(this)"
                        class="appearance-none w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5 pl-3 pr-9 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                        <option value="">{{ __('All Trainings') }}</option>
                        @foreach ($trainingTitles as $title)
                            <option value="{{ $title }}" @selected($filters['training_title'] === $title)>{{ $title }}</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>
        </form>
    </x-chart-card>

    @include('admin.partials.performance-stat-cards', ['summary' => $monitoringSummary])

    <!-- Map -->
    <x-chart-card body-class="pt-4"
        :title="__('Graduates by Location')">

        @if ($monitoringSummary['trainings'] === 0)
            <div class="mx-6 sm:mx-8 mb-6 sm:mb-8 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                {{ __('No completed trainings match these filters yet.') }}
            </div>
        @else
            @include('admin.partials.city-map', ['mapId' => 'dashboardCityMap', 'cityTotals' => $cityTotals, 'onlyRegion' => $region, 'height' => 600])
        @endif
    </x-chart-card>
</div>
