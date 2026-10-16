{{--
    The overview charts. They have no filters of their own — every one follows
    the From / Until / Region / Training filter at the top of the dashboard
    (see dashboard-monitoring.blade.php), and is re-rendered with the rest of
    dashboard-body.blade.php when it changes.
--}}
@php
    $chartRegionLabel = $region ?: __('every region');
@endphp
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
    {{-- Replaces the old request-pipeline chart: TA has no targets set against
         it, so the honest comparison is what was put on the calendar against
         what was delivered. Labelled "Scheduled", not "Requested": nobody but
         Super Admin can create a TrainingRequest right now (participants and
         Regional Admin are read-only, and SOURCE_PUBLIC_PORTAL is unused since
         the public form was pulled), so the navy bar is trainings this office
         scheduled, not demand coming in from outside. The underlying array
         keys are still requested/accomplished. --}}
    <x-chart-card :title="__('Scheduled vs Completed')"
        :subtitle="__('Technical Assistance trainings scheduled against those marked completed, running total by month, :region, :period.', ['region' => $chartRegionLabel, 'period' => $scheduledPeriod])">
        @if (collect($chartData['requestedVsAccomplished'])->sum('requested') > 0)
            <div class="h-72"><canvas id="dashRequestedVsAccomplishedChart"></canvas></div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No Technical Assistance trainings on record for this period.') }}</p>
        @endif
    </x-chart-card>

    {{-- Two charts, one per measure, each on its own zero-based axis. A single
         grouped chart needed twin axes to keep the trainings bars visible
         against ~17x more graduates, and that made 270 trainings and 4,592
         graduates draw at the same height — which read as if they were equal.
         The comparison that matters is APB against TA within a measure, not
         trainings against graduates. --}}
    <x-chart-card :title="__('APB vs Technical Assistance')"
        :subtitle="__('Completed trainings and the graduates they produced, :region. Each measure has its own scale.', ['region' => $chartRegionLabel])">
        @if (collect($chartData['categoryComparison'])->sum('trainings') > 0)
            @php $comparison = collect($chartData['categoryComparison']); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2 text-center">{{ __('Trainings Conducted') }}</p>
                    <div class="h-56"><canvas id="dashCategoryTrainingsChart"></canvas></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2 text-center">{{ __('Graduates Produced') }}</p>
                    <div class="h-56"><canvas id="dashCategoryGraduatesChart"></canvas></div>
                </div>
            </div>

            {{-- The figure the two charts can't show on their own. --}}
            <div class="grid grid-cols-2 gap-3 mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                @foreach ($comparison as $row)
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $row['label'] }}</p>
                        <p class="text-lg font-bold text-[#152A4E] dark:text-white tabular-nums">
                            {{ $row['trainings'] > 0 ? number_format($row['graduates'] / $row['trainings'], 1) : '—' }}
                        </p>
                        <p class="text-xs text-gray-400">{{ __('graduates per training') }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings yet.') }}</p>
        @endif
    </x-chart-card>

    {{-- A line per training, so several can be compared at once. Trainings
         are ticked in the checklist in the header — up to six, past which the
         lines stop being easy to tell apart — and the busiest three start
         ticked. Ticking happens in the browser, since every training's figures
         are already in the payload (see renderDashboardCharts() in
         dashboard.blade.php). With the dashboard's Training filter set there's
         only that one training, so the checklist goes away. --}}
    @php
        $trendTrainings = $chartData['threeYearTrend']['trainings'];
        $trendYears = $chartData['threeYearTrend']['years'];
    @endphp
    <x-chart-card :title="__('Three-Year Trend')"
        :subtitle="count($trendTrainings) > 1
            ? __('Graduates per training, :first–:last, :region. Tick up to six trainings to compare.', ['first' => $trendYears[0], 'last' => last($trendYears), 'region' => $chartRegionLabel])
            : __('Graduates, :first–:last, :region.', ['first' => $trendYears[0], 'last' => last($trendYears), 'region' => $chartRegionLabel])">
        @if (count($trendTrainings) > 1)
            <x-slot:action>
                <div x-data="{ open: false, alignLeft: false }" class="relative" @keydown.escape.window="open = false">
                    <button type="button" @click="alignLeft = $el.getBoundingClientRect().right < 340; open = !open" :aria-expanded="open.toString()" aria-haspopup="true"
                        class="inline-flex items-center gap-2 rounded-md bg-white text-[#152A4E] text-sm font-semibold py-1.5 pl-3 pr-2 focus:outline-none focus:ring-2 focus:ring-white/60">
                        <span id="trendSelectionLabel">{{ __('Compare trainings') }}</span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" :class="alignLeft ? 'left-0' : 'right-0'"
                        class="absolute z-20 mt-1.5 w-80 max-w-[calc(100vw-3rem)] rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg">
                        <div class="flex items-center justify-between gap-3 px-3 pt-2.5 pb-1.5">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Tick up to 6 to compare') }}</p>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Graduates') }}</p>
                        </div>
                        <div class="max-h-56 overflow-y-auto px-1.5 pb-1.5 space-y-0.5">
                            @foreach ($trendTrainings as $index => $row)
                                <label class="flex items-start gap-2 px-2 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/60 text-sm text-gray-700 dark:text-gray-200 cursor-pointer has-[:disabled]:opacity-50 has-[:disabled]:cursor-not-allowed">
                                    <input type="checkbox" value="{{ $index }}" @checked($index < 3)
                                        class="trend-training-option mt-0.5 rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E] disabled:cursor-not-allowed">
                                    <span class="min-w-0">{{ $row['training'] }}</span>
                                    <span class="ml-auto shrink-0 text-xs text-gray-400 tabular-nums">{{ array_sum($row['graduates']) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-slot:action>
        @endif
        @if (count($trendTrainings) > 0)
            <div class="h-72"><canvas id="dashThreeYearTrendChart"></canvas></div>

            {{-- The same figures as text, for anyone who can't tell the line colours apart. --}}
            <details class="mt-4">
                <summary class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white cursor-pointer select-none">{{ __('View as table') }}</summary>
                <div class="overflow-x-auto mt-3">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 pr-4">{{ __('Training') }}</th>
                                @foreach ($trendYears as $trendYear)
                                    <th class="py-2 pr-4 text-right">{{ $trendYear }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="dashThreeYearTrendTable" class="divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                    </table>
                </div>
            </details>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings in these three years.') }}</p>
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
