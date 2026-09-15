{{--
    Regional Performance & Graduates Map section — filterable by date and
    training here; region comes from the shared "Filter Charts by Region"
    control above rather than its own field, carried through as a hidden
    input so this form's own submission doesn't reset it. Every field
    auto-submits on change instead of needing an "Apply Filters" button —
    see submitDashboardFilter() in dashboard.blade.php.
--}}
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8 space-y-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Regional Performance & Graduates Map') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Every training on file is Technical Assistance. Filter by date or training below — use "Filter Charts by Region" above for region.') }}</p>
            </div>
            @if ($chartRegion || $monitoringFilters['training_title'] || $monitoringFilters['from'] || $monitoringFilters['until'])
                <a href="{{ route('admin.dashboard') }}" onclick="return submitDashboardFilterReset(event)" class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                    {{ __('Reset') }}
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" onsubmit="return submitDashboardFilter(this, event)">
            @if ($chartRegion)
                <input type="hidden" name="chart_region" value="{{ $chartRegion }}">
            @endif
            <div>
                <label for="from" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('From') }}</label>
                <input type="date" id="from" name="from" value="{{ $monitoringFilters['from'] }}" onchange="submitDashboardFilter(this)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:[color-scheme:dark] text-sm py-2.5 px-3 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
            </div>
            <div>
                <label for="until" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Until') }}</label>
                <input type="date" id="until" name="until" value="{{ $monitoringFilters['until'] }}" onchange="submitDashboardFilter(this)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:[color-scheme:dark] text-sm py-2.5 px-3 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
            </div>
            <div class="sm:col-span-2">
                <label for="training_title" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Training') }}</label>
                <div class="relative">
                    <select id="training_title" name="training_title" onchange="submitDashboardFilter(this)"
                        class="appearance-none w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5 pl-3 pr-9 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                        <option value="">{{ __('All Trainings') }}</option>
                        @foreach ($trainingTitles as $title)
                            <option value="{{ $title }}" @selected($monitoringFilters['training_title'] === $title)>{{ $title }}</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
            </div>
        </form>
    </div>

    <!-- Regional Performance stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        @foreach ([
            ['label' => 'Trainings Conducted', 'value' => $monitoringSummary['trainings'], 'hint' => null, 'accent' => '#2a78d6'],
            ['label' => 'Total Participants', 'value' => $monitoringSummary['participants'], 'hint' => $monitoringSummary['non_completers'].' non-completers', 'accent' => '#152A4E'],
            ['label' => 'Graduates', 'value' => $monitoringSummary['graduates'], 'hint' => $monitoringSummary['completion_rate'].' completion', 'accent' => '#0ca30c'],
            ['label' => 'LGUs Covered', 'value' => $monitoringSummary['lgus'], 'hint' => null, 'accent' => '#03055A'],
            ['label' => 'NGAs Covered', 'value' => $monitoringSummary['ngas'], 'hint' => null, 'accent' => '#E2762D'],
        ] as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 border-l-4" style="border-left-color: {{ $card['accent'] }};">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 min-h-[2rem]">{{ __($card['label']) }}</div>
                <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $card['value'] }}</div>
                @if ($card['hint'])
                    <div class="text-xs text-gray-400 mt-0.5">{{ $card['hint'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Regional Highlights -->
    @if (! empty($regionalHighlights))
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ($regionalHighlights as $highlight)
                <div class="flex items-center gap-3 bg-[#152A4E] dark:bg-[#0D1B33] rounded-xl px-5 py-4">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-white/10 flex items-center justify-center text-[#E2762D]">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-white/50">{{ __($highlight['label']) }}</div>
                        <div class="text-sm font-bold text-white truncate">{{ $highlight['region'] }}</div>
                        <div class="text-xs text-white/60">{{ $highlight['value'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Region table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
        <h3 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Overall Training Data (OCDROs and Central)') }}</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <th class="py-2 pr-4">{{ __('OCD Regional Office') }}</th>
                        <th class="py-2 pr-4">{{ __('Trainings') }}</th>
                        <th class="py-2 pr-4">{{ __('Participants') }}</th>
                        <th class="py-2 pr-4">{{ __('Graduates') }}</th>
                        <th class="py-2 pr-4">{{ __('Teams') }}</th>
                        <th class="py-2 pr-4">{{ __('LGUs') }}</th>
                        <th class="py-2 pr-4">{{ __('NGAs') }}</th>
                        <th class="py-2 pr-4">{{ __('Completion') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($regionalData as $row)
                        <tr @class(['font-semibold bg-gray-50 dark:bg-gray-700/40' => $row['label'] === 'Central (All OCDROs)'])>
                            <td class="py-3 pr-4 text-[#152A4E] dark:text-white">{{ $row['label'] === 'Central (All OCDROs)' ? $row['label'] : 'OCDRO '.$row['short_label'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['trainings'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['participants'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['graduates'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['teams'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['lgus'] }}</td>
                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['ngas'] }}</td>
                            <td class="py-3 pr-4">
                                @php $rate = (float) $row['completion_rate']; @endphp
                                <div class="flex items-center gap-2 min-w-[7rem]">
                                    <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                        <div class="h-full rounded-full bg-[#0ca30c]" style="width: {{ min(100, $rate) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 tabular-nums whitespace-nowrap">{{ $row['completion_rate'] }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings match these filters yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Map -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 sm:px-8 pt-6 sm:pt-8 pb-4">
            <h3 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Location') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Marker size scales with graduate count — hover a marker for its details, or hover anywhere in a region to see that region\'s totals.') }}</p>
        </div>

        @if (empty($mapPoints))
            <div class="mx-6 sm:mx-8 mb-6 sm:mb-8 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                {{ __('No completed trainings match these filters yet.') }}
            </div>
        @else
            <div class="dash-graduates-map-panel px-3 pb-3">
                <div id="dashboardGraduatesMap" style="height: 480px; border-radius: 0.5rem;"></div>
            </div>

            <div class="overflow-x-auto p-6 pt-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2 pr-4">{{ __('LGU / NGA') }}</th>
                            <th class="py-2 pr-4">{{ __('Region') }}</th>
                            <th class="py-2 pr-4">{{ __('Trainings') }}</th>
                            <th class="py-2 pr-4">{{ __('Graduates') }}</th>
                            <th class="py-2 pr-4">{{ __('Teams') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach (array_slice($mapPoints, 0, 15) as $point)
                            @php $dotColor = $point['agency_type'] === 'LGU' ? '#03055A' : ($point['agency_type'] === 'NGA' ? '#E2762D' : '#0EA5E9'); @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $dotColor }};"></span>
                                        <div>
                                            <div class="font-medium text-[#152A4E] dark:text-white">{{ $point['name'] }}</div>
                                            @if ($point['agency_type'])
                                                <div class="text-xs text-gray-400">{{ $point['agency_type'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $point['region'] }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['trainings'] }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['graduates'] }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['teams'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (count($mapPoints) > 15)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">{{ __('Top 15 of :count locations, by graduate count.', ['count' => count($mapPoints)]) }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
