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

    {{-- One training at a time, chosen from the header dropdown. Plotting all
         28 at once left each bar a few pixels tall and clipped the longer
         titles; the switch is client-side because every training's figures are
         already in the payload. With the dashboard's Training filter set
         there's only that one training, so the dropdown goes away. --}}
    <x-chart-card :title="__('Three-Year Trend')"
        :subtitle="__('Graduates for one training, :first–:last, :region.', ['first' => $chartData['threeYearTrend']['years'][0], 'last' => last($chartData['threeYearTrend']['years']), 'region' => $chartRegionLabel])">
        @if (count($chartData['threeYearTrend']['trainings']) > 1)
            <x-slot:action>
                <label for="trend_training" class="sr-only">{{ __('Training') }}</label>
                <select id="trend_training"
                    class="max-w-[16rem] rounded-md border-transparent bg-white text-[#152A4E] text-sm font-semibold py-1.5 focus:border-white focus:ring-2 focus:ring-white/60">
                    @foreach ($chartData['threeYearTrend']['trainings'] as $index => $row)
                        <option value="{{ $index }}">{{ $row['training'] }}</option>
                    @endforeach
                </select>
            </x-slot:action>
        @endif
        @if (count($chartData['threeYearTrend']['trainings']) > 0)
            <div class="h-64"><canvas id="dashThreeYearTrendChart"></canvas></div>
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
