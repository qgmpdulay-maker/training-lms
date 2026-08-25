<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Regional Monitoring') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400 mb-4">{{ __('Selected Period') }}</h2>
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
                        <x-input-label for="regions" :value="__('Regions')" />
                        <select id="regions" name="regions[]" multiple size="1"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected(in_array($region, $filters['regions']))>{{ $region }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">{{ __('Leave blank for all regions.') }}</p>
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
                    <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-5 py-2.5 hover:bg-[#1E3A66] transition">
                            {{ __('Apply Filters') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                @foreach ([
                    ['label' => 'Trainings Conducted', 'value' => $summary['trainings'], 'hint' => $summary['apb'].' APB / '.$summary['ta'].' TA'],
                    ['label' => 'Total Participants', 'value' => $summary['participants'], 'hint' => $summary['non_completers'].' non-completers'],
                    ['label' => 'Graduates', 'value' => $summary['graduates'], 'hint' => $summary['completion_rate'].' completion'],
                    ['label' => 'Teams Organized', 'value' => $summary['teams'], 'hint' => null],
                    ['label' => 'LGUs Covered', 'value' => $summary['lgus'], 'hint' => null],
                    ['label' => 'NGAs Covered', 'value' => $summary['ngas'], 'hint' => null],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __($card['label']) }}</div>
                        <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $card['value'] }}</div>
                        @if ($card['hint'])
                            <div class="text-xs text-gray-400 mt-0.5">{{ $card['hint'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Showing completed trainings from :period.', ['period' => $periodLabel]) }}</p>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Training Conducted (APB and TA)') }}</h3>
                    <div class="h-64"><canvas id="trainingsConductedChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Graduates and Teams Organized per Region') }}</h3>
                    <div class="h-64"><canvas id="graduatesPerRegionChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Graduates Demographics (Sex)') }}</h3>
                    <div class="h-64"><canvas id="graduatesBySexChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Graduates Demographics (Age Range)') }}</h3>
                    <div class="h-64"><canvas id="graduatesByAgeRangeChart"></canvas></div>
                </div>
            </div>

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
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['trainings'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['apb'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['ta'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['participants'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['graduates'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['teams'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['lgus'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['ngas'] }}</td>
                                    <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['completion_rate'] }}</td>
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
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row[$key] }}</td>
                                    @endforeach
                                    <td class="py-3 pr-4 font-semibold text-gray-700 dark:text-gray-200">{{ collect($threeYearData)->sum($key) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    @endpush

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
                    { label: 'APB', data: trainingsConducted.map(row => row.apb), borderColor: brandNavy, backgroundColor: 'rgba(3,5,90,.15)', tension: 0.3, fill: true },
                    { label: 'TA', data: trainingsConducted.map(row => row.ta), borderColor: brandOrange, backgroundColor: 'rgba(226,118,45,.2)', tension: 0.3, fill: true },
                ],
            },
            options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });

        const graduatesPerRegion = @json($chartData['graduatesPerRegion']);
        new Chart(document.getElementById('graduatesPerRegionChart'), {
            type: 'bar',
            data: {
                labels: graduatesPerRegion.map(row => row.region),
                datasets: [
                    { label: 'Graduates', data: graduatesPerRegion.map(row => row.graduates), backgroundColor: brandNavy },
                    { label: 'Teams Organized', data: graduatesPerRegion.map(row => row.teams), backgroundColor: brandOrange },
                ],
            },
            options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } },
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
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } } },
        });
    </script>
</x-app-layout>
