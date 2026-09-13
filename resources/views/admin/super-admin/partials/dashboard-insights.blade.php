{{--
    Auto-generated callouts from the chart data — depends on chart_region/year,
    so it's re-rendered alongside dashboard-charts.blade.php (see
    submitDashboardFilter() in dashboard.blade.php).
--}}
@if (count($insights) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Summary & Insights') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Auto-generated from the charts above.') }}</p>
        <ul class="space-y-2.5">
            @foreach ($insights as $insight)
                <li class="text-sm text-gray-600 dark:text-gray-300">{{ $insight }}</li>
            @endforeach
        </ul>
    </div>
@endif
