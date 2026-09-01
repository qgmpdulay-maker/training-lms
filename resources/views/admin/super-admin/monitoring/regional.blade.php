<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Regional Monitoring') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Selected Period') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Showing completed trainings from :period.', ['period' => $periodLabel]) }}</p>
                </div>
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="from" :value="__('From')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full text-sm" value="{{ $filters['from'] }}" />
                    </div>
                    <div>
                        <x-input-label for="until" :value="__('Until')" />
                        <x-text-input id="until" name="until" type="date" class="mt-1 block w-full text-sm" value="{{ $filters['until'] }}" />
                    </div>
                    <div>
                        <x-input-label :value="__('Regions')" />
                        <div x-data="{ open: false }" class="relative mt-1">
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between gap-2 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3 text-left hover:border-[#152A4E] dark:hover:border-white/40 transition">
                                <span class="truncate">
                                    @if (empty($filters['regions']))
                                        {{ __('All Regions') }}
                                    @elseif (count($filters['regions']) === 1)
                                        {{ $filters['regions'][0] }}
                                    @else
                                        {{ __(':count regions selected', ['count' => count($filters['regions'])]) }}
                                    @endif
                                </span>
                                <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                            <div x-show="open" @click.outside="open = false" x-cloak
                                class="absolute z-20 mt-1.5 w-full max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg p-1.5 space-y-0.5">
                                @foreach ($regions as $regionOption)
                                    <label class="flex items-center gap-2 px-2.5 py-1.5 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700/60 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                                        <input type="checkbox" name="regions[]" value="{{ $regionOption }}" @checked(in_array($regionOption, $filters['regions']))
                                            class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                                        {{ $regionOption }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <x-input-label for="category" :value="__('APB / TA')" />
                        <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">{{ __('APB and TA') }}</option>
                            @foreach ($categoryLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end gap-3">
                        @if (! empty($filters['regions']) || $filters['category'])
                            <a href="{{ route('admin.monitoring.regional', array_filter(['from' => $filters['from'], 'until' => $filters['until']])) }}"
                                class="inline-flex items-center text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Reset') }}
                            </a>
                        @endif
                        <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Apply Filters') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
                @foreach ([
                    ['label' => 'Trainings Conducted', 'value' => $summary['trainings'], 'hint' => $summary['apb'].' APB / '.$summary['ta'].' TA', 'accent' => '#2a78d6'],
                    ['label' => 'Total Participants', 'value' => $summary['participants'], 'hint' => $summary['non_completers'].' non-completers', 'accent' => '#152A4E'],
                    ['label' => 'Graduates', 'value' => $summary['graduates'], 'hint' => $summary['completion_rate'].' completion', 'accent' => '#0ca30c'],
                    ['label' => 'Teams Organized', 'value' => $summary['teams'], 'hint' => null, 'accent' => '#E2762D'],
                    ['label' => 'LGUs Covered', 'value' => $summary['lgus'], 'hint' => null, 'accent' => '#03055A'],
                    ['label' => 'NGAs Covered', 'value' => $summary['ngas'], 'hint' => null, 'accent' => '#E2762D'],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 border-l-4" style="border-left-color: {{ $card['accent'] }};">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __($card['label']) }}</div>
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

            <!-- Primary chart: Training Conducted -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h3 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Conducted (APB and TA)') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings over the selected period, split by category.') }}</p>
                <div class="h-80 sm:h-96"><canvas id="trainingsConductedChart"></canvas></div>
            </div>

            <!-- Demographics charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Graduates Demographics (Sex)') }}</h3>
                    <div class="h-72"><canvas id="graduatesBySexChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Graduates Demographics (Age Range)') }}</h3>
                    <div class="h-72"><canvas id="graduatesByAgeRangeChart"></canvas></div>
                </div>
            </div>

            <!-- Graduates by Region -->
            @if (count($regionalData) > 1)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h3 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Region') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Every region with at least one completed training in this period, ranked highest first.') }}</p>
                    <div style="height: {{ max(240, (count($regionalData) - 1) * 34) }}px"><canvas id="graduatesByRegionChart"></canvas></div>
                </div>
            @endif

            <!-- Region table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Overall Training Data (OCDROs and Central)') }}</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 pr-4">{{ __('OCD Regional Office') }}</th>
                                <th class="py-2 pr-4">{{ __('Trainings') }}</th>
                                <th class="py-2 pr-4">{{ __('APB') }}</th>
                                <th class="py-2 pr-4">{{ __('TA') }}</th>
                                <th class="py-2 pr-4">{{ __('Participants') }}</th>
                                <th class="py-2 pr-4">{{ __('Graduates') }}</th>
                                <th class="py-2 pr-4">{{ __('Teams') }}</th>
                                <th class="py-2 pr-4">{{ __('LGUs') }}</th>
                                <th class="py-2 pr-4">{{ __('NGAs') }}</th>
                                <th class="py-2 pr-4">{{ __('Completion') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($regionalData as $row)
                                <tr @class(['font-semibold bg-gray-50 dark:bg-gray-700/40' => $row['label'] === 'Central (All OCDROs)'])>
                                    <td class="py-3 pr-4 text-[#152A4E] dark:text-white">{{ $row['label'] === 'Central (All OCDROs)' ? $row['label'] : 'OCDRO '.$row['short_label'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['trainings'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['apb'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row['ta'] }}</td>
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Three year table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Three Year Data Generation') }}</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <th class="py-2 pr-4">{{ __('Metric') }}</th>
                                @foreach ($threeYearData as $year => $row)
                                    <th class="py-2 pr-4">{{ $year }}</th>
                                @endforeach
                                <th class="py-2 pr-4">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ([
                                'trainings' => 'Trainings Conducted',
                                'apb' => 'Training Conducted (APB)',
                                'ta' => 'Technical Assistance (TA)',
                                'participants' => 'Total Participants',
                                'graduates' => 'Graduates',
                                'non_completers' => 'Participation Only (Non-Completers)',
                                'teams' => 'Teams Organized',
                            ] as $key => $label)
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ __($label) }}</td>
                                    @foreach ($threeYearData as $row)
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $row[$key] }}</td>
                                    @endforeach
                                    <td class="py-3 pr-4 font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ collect($threeYearData)->sum($key) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const brandNavy = '#03055A';
        const brandOrange = '#E2762D';

        const trainingsConducted = @json($chartData['trainingsConducted']);
        new Chart(document.getElementById('trainingsConductedChart'), {
            type: 'line',
            data: {
                labels: trainingsConducted.map(row => row.label),
                datasets: [
                    { label: 'APB', data: trainingsConducted.map(row => row.apb), borderColor: brandNavy, backgroundColor: 'rgba(3,5,90,.15)', tension: 0.3, fill: true, pointRadius: 3 },
                    { label: 'TA', data: trainingsConducted.map(row => row.ta), borderColor: brandOrange, backgroundColor: 'rgba(226,118,45,.2)', tension: 0.3, fill: true, pointRadius: 3 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        const bySex = @json($chartData['graduatesBySex']);
        new Chart(document.getElementById('graduatesBySexChart'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{ data: [bySex.male, bySex.female], backgroundColor: [brandNavy, brandOrange] }],
            },
            options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
        });

        const byAge = @json($chartData['graduatesByAgeRange']);
        new Chart(document.getElementById('graduatesByAgeRangeChart'), {
            type: 'bar',
            data: {
                labels: ['18 - 30', '31 - 45', '46 - 59', '60 and above'],
                datasets: [{
                    data: [byAge.age_18_30, byAge.age_31_45, byAge.age_46_59, byAge.age_60_up],
                    backgroundColor: [brandNavy, '#3B4FA8', brandOrange, '#F0A868'],
                }],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        });

        const regionRows = @json(collect($regionalData)->reject(fn ($row) => $row['label'] === 'Central (All OCDROs)')->values());
        const regionChartEl = document.getElementById('graduatesByRegionChart');
        if (regionChartEl && regionRows.length) {
            new Chart(regionChartEl, {
                type: 'bar',
                data: {
                    labels: regionRows.map(row => row.short_label),
                    datasets: [{ label: 'Graduates', data: regionRows.map(row => row.graduates), backgroundColor: brandNavy, borderRadius: 4 }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }
    </script>
</x-app-layout>
