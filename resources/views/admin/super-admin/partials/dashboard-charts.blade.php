{{--
    The "Filter Charts by Region" card + the five overview charts grid.
    Re-rendered in place (no page reload) whenever the chart_region or year
    select changes — see submitDashboardFilter() in dashboard.blade.php.
--}}
@php
    $chartRegionLabel = $chartRegion ?: __('every region');
@endphp
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 sm:px-6 py-4 flex items-center justify-between flex-wrap gap-4 mb-6">
    <div class="flex items-center gap-3">
        <div class="h-11 w-11 shrink-0 rounded-lg bg-[#E2762D]/10 dark:bg-[#E2762D]/20 flex items-center justify-center text-[#E2762D]">
            @include('admin.partials.icon', ['name' => 'map'])
        </div>
        <div>
            <div class="font-bold text-[#152A4E] dark:text-white leading-tight">{{ __('Filter Charts by Region') }}</div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Applies to the five overview charts below.') }}</p>
        </div>
    </div>
    <form method="GET" action="{{ route('admin.dashboard') }}" onsubmit="return submitDashboardFilter(this, event)">
        <input type="hidden" name="year" value="{{ $year }}">
        @if (! empty($monitoringFilters['training_title']))
            <input type="hidden" name="training_title" value="{{ $monitoringFilters['training_title'] }}">
        @endif
        @if (! empty($monitoringFilters['from']))
            <input type="hidden" name="from" value="{{ $monitoringFilters['from'] }}">
        @endif
        @if (! empty($monitoringFilters['until']))
            <input type="hidden" name="until" value="{{ $monitoringFilters['until'] }}">
        @endif
        <label for="chart_region" class="sr-only">{{ __('Region') }}</label>
        <div class="relative">
            <select id="chart_region" name="chart_region" onchange="submitDashboardFilter(this)"
                class="appearance-none min-w-[180px] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-semibold py-2.5 pl-4 pr-10 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                <option value="" @selected(! $chartRegion)>{{ __('All Regions') }}</option>
                @foreach ($regions as $regionOption)
                    <option value="{{ $regionOption }}" @selected($chartRegion === $regionOption)>{{ $regionOption }}</option>
                @endforeach
            </select>
            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </div>
    </form>
</div>
<div class="grid grid-cols-1 gap-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-chart-card :title="__('Graduates by Sex')"
            :subtitle="__('Completed trainings only, :region.', ['region' => $chartRegionLabel])">
            @if ($chartData['graduatesBySex']['male'] + $chartData['graduatesBySex']['female'] > 0)
                <div class="h-64 max-w-xs mx-auto"><canvas id="dashGraduatesBySexChart"></canvas></div>
            @else
                <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings yet.') }}</p>
            @endif
        </x-chart-card>
        <x-chart-card :title="__('Graduates by Age Range')"
            :subtitle="__('Completed trainings only, :region.', ['region' => $chartRegionLabel])">
            <div class="h-64"><canvas id="dashGraduatesByAgeRangeChart"></canvas></div>
        </x-chart-card>
    </div>
    <x-chart-card :title="__('Graduates by Training')"
        :subtitle="__('Completed trainings only, :region — every course in the catalog is Technical Assistance.', ['region' => $chartRegionLabel])">
        <x-slot:action>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2" onsubmit="return submitDashboardFilter(this, event)">
                @if ($chartRegion)
                    <input type="hidden" name="chart_region" value="{{ $chartRegion }}">
                @endif
                @if (! empty($monitoringFilters['training_title']))
                    <input type="hidden" name="training_title" value="{{ $monitoringFilters['training_title'] }}">
                @endif
                @if (! empty($monitoringFilters['from']))
                    <input type="hidden" name="from" value="{{ $monitoringFilters['from'] }}">
                @endif
                @if (! empty($monitoringFilters['until']))
                    <input type="hidden" name="until" value="{{ $monitoringFilters['until'] }}">
                @endif
                <label for="year" class="text-xs font-semibold text-white/70">{{ __('Year') }}</label>
                <select id="year" name="year" onchange="submitDashboardFilter(this)"
                    class="rounded-md border-transparent bg-white text-[#152A4E] text-sm font-semibold py-1.5 focus:border-white focus:ring-2 focus:ring-white/60">
                    @foreach ($availableYears as $yearOption)
                        <option value="{{ $yearOption }}" @selected((string) $year === (string) $yearOption)>{{ $yearOption }}</option>
                    @endforeach
                    <option value="all" @selected($year === 'all')>{{ __('All Years') }}</option>
                </select>
            </form>
        </x-slot:action>
        @if (count($chartData['graduatesByTraining']) > 0)
            <div style="height: {{ max(240, count($chartData['graduatesByTraining']) * 34) }}px"><canvas id="dashGraduatesByTrainingChart"></canvas></div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ $year === 'all' ? __('No completed trainings yet.') : __('No completed trainings for :year.', ['year' => $year]) }}</p>
        @endif
    </x-chart-card>
    {{-- Replaces the old request-pipeline chart: TA has no targets set against
         it, so the honest comparison is asked-for against delivered. --}}
    <x-chart-card :title="__('Requested vs Accomplished')"
        :subtitle="__('Technical Assistance trainings, running total by month, :region :year.', ['region' => $chartRegionLabel, 'year' => $year === 'all' ? __('(all years)') : $year])">
        @if (collect($chartData['requestedVsAccomplished'])->sum('requested') > 0)
            <div class="h-72"><canvas id="dashRequestedVsAccomplishedChart"></canvas></div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No Technical Assistance trainings on record for this period.') }}</p>
        @endif
    </x-chart-card>

    <x-chart-card :title="__('APB vs Technical Assistance')"
        :subtitle="__('Completed trainings and the graduates they produced, :region.', ['region' => $chartRegionLabel])">
        @if (collect($chartData['categoryComparison'])->sum('trainings') > 0)
            {{-- Only two groups, so the canvas is capped rather than stretched
                 across the full card width. --}}
            <div class="h-64 max-w-2xl mx-auto"><canvas id="dashCategoryComparisonChart"></canvas></div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings yet.') }}</p>
        @endif
    </x-chart-card>

    {{-- One training at a time, chosen from the header dropdown. Plotting all
         28 at once left each bar a few pixels tall and clipped the longer
         titles; the switch is client-side because every training's figures are
         already in the payload. --}}
    <x-chart-card :title="__('Three-Year Trend')"
        :subtitle="__('Graduates for one training over the last three years, :region.', ['region' => $chartRegionLabel])">
        @if (count($chartData['threeYearTrend']['trainings']) > 0)
            <x-slot:action>
                <label for="trend_training" class="sr-only">{{ __('Training') }}</label>
                <select id="trend_training"
                    class="max-w-[16rem] rounded-md border-transparent bg-white text-[#152A4E] text-sm font-semibold py-1.5 focus:border-white focus:ring-2 focus:ring-white/60">
                    @foreach ($chartData['threeYearTrend']['trainings'] as $index => $row)
                        <option value="{{ $index }}">{{ $row['training'] }}</option>
                    @endforeach
                </select>
            </x-slot:action>
            <div class="h-64"><canvas id="dashThreeYearTrendChart"></canvas></div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings in the last three years.') }}</p>
        @endif
    </x-chart-card>

    <x-chart-card :title="__('Most Needed Trainings')"
        :subtitle="__('What the Training Needs Assessment says participants need most, :region.', ['region' => $chartRegionLabel])">
        @if (count($chartData['mostNeededTrainings']) > 0)
            <div style="height: {{ max(240, count($chartData['mostNeededTrainings']) * 34) }}px"><canvas id="dashMostNeededTrainingsChart"></canvas></div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No Training Needs Assessment submissions yet.') }}</p>
        @endif
    </x-chart-card>
</div>
