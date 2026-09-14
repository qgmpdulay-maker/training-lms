{{--
    Auto-generated callouts from the chart data — depends on chart_region/year,
    so it's re-rendered alongside dashboard-charts.blade.php (see
    submitDashboardFilter() in dashboard.blade.php).
--}}
@if (count($insights) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Summary & Insights') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Auto-generated from the charts above.') }}</p>
        <ul class="space-y-3">
            @foreach ($insights as $insight)
                <li class="flex items-start gap-2.5 text-sm text-gray-600 dark:text-gray-300">
                    <svg class="w-4 h-4 text-[#E2762D] shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.454 1.405 1.02L10 15.591l4.069 2.485c.713.435 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $insight }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
