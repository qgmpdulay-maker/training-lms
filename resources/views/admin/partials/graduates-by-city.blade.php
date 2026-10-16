{{--
    Shared "Graduates by City" card, used by both admin dashboards: the
    Graduates by Location map's figures as a ranked list, one tab per region.
    Expects $graduatesByCity (DashboardController::graduatesByCity()),
    $cityTotals (for the not-on-the-map count), and $region (the region
    filter, or a Regional Admin's own region; null for every region).
--}}
<x-chart-card :title="__('Graduates by City')" body-class="p-6 sm:p-8">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        {{ __('Graduates grouped by the city or municipality they live in — the same figures as the Graduates by Location map.') }}
        {{ __('For the city map on a page of its own, see the') }}
        <a href="{{ route('admin.monitoring.map', $region ? ['regions' => [$region]] : []) }}" class="font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D]">{{ __('Graduates Map') }}</a>.
    </p>
    <p class="text-xs text-amber-700 dark:text-amber-400 mb-5">
        {{ __('"Teams Organized" isn\'t shown here since there\'s no team data in the system yet.') }}
        @if ($cityTotals['unplaced'] > 0)
            <br>
            {{ trans_choice(':count graduate isn\'t listed|:count graduates aren\'t listed', $cityTotals['unplaced'], ['count' => number_format($cityTotals['unplaced'])]) }}
            {{ __('— no City on file, or one that isn\'t a city or municipality we recognise.') }}
            <a href="{{ route('admin.deployments.index', ['status' => 'no_city']) }}" class="font-semibold underline hover:no-underline">{{ __('Set their city') }}</a>
        @endif
    </p>

    @if (empty($graduatesByCity))
        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
            {{ __('No graduates with a recognised City on file yet.') }}
        </div>
    @else
        <div x-data="{ activeCityRegion: @js(array_key_first($graduatesByCity)) }">
            @if (count($graduatesByCity) > 1)
                <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5 mb-5">
                    @foreach ($graduatesByCity as $regionName => $regionGroup)
                        <button type="button" @click="activeCityRegion = @js($regionName)"
                            :class="activeCityRegion === @js($regionName)
                                ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                            {{ $regionName }}
                            <span :class="activeCityRegion === @js($regionName)
                                    ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                    : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                {{ number_format($regionGroup['total']) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach ($graduatesByCity as $regionName => $regionGroup)
                @php $regionMax = max(array_column($regionGroup['cities'], 'total')); @endphp
                <div x-show="activeCityRegion === @js($regionName)" @if (! $loop->first) x-cloak @endif class="space-y-3">
                    @foreach ($regionGroup['cities'] as $row)
                        <div class="flex items-center gap-3">
                            <div class="w-56 shrink-0 text-xs truncate" title="{{ $row['name'] }}, {{ $row['province'] }}">
                                <span class="font-medium text-gray-600 dark:text-gray-300">{{ $row['name'] }}</span>
                                <span class="text-gray-400 dark:text-gray-500">{{ $row['province'] }}</span>
                            </div>
                            <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D] transition-all" style="width: {{ round(($row['total'] / $regionMax) * 100) }}%;"></div>
                            </div>
                            <div class="w-10 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ number_format($row['total']) }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</x-chart-card>
