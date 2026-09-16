{{--
    Charts sourced from imported ATAR records (see AtarRecordController) —
    reused as-is by both the super-admin ajax-refreshed dashboard partial and
    the regional dashboard, which just @includes this directly.
--}}
@php
    $atarHasData = count($chartData['atarTrainingsByMode']) > 0;
@endphp
<x-chart-card :title="__('Training Database (ATAR Records)')"
    :subtitle="__('Historical accomplishment data imported from Training Database CSV exports, :region.', ['region' => $chartRegionLabel ?? __('all regions')])">
    @if (! $atarHasData)
        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No ATAR records imported yet.') }}</p>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">{{ __('Trainings by Mode of Implementation') }}</h4>
                <div class="h-56 max-w-xs mx-auto"><canvas id="dashAtarModeChart"></canvas></div>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">{{ __('Trainings by Month') }}</h4>
                <div class="h-56"><canvas id="dashAtarMonthChart"></canvas></div>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">{{ __('Graduates by Sector') }}</h4>
                <div class="h-56"><canvas id="dashAtarSectorChart"></canvas></div>
            </div>
        </div>
    @endif
</x-chart-card>
