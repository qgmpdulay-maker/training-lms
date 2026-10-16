<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Regional Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Welcome Banner -->
            @include('admin.partials.welcome-banner', [
                'eyebrow' => __('OCD Regional Office').($user->region ? ' — '.$user->region : ''),
                'description' => __('Regional admin access — manage your region\'s trainings, instructors, and reports below.'),
            ])

            <!-- Regional Performance & Graduates Map — first thing under the welcome banner.
                 Same section as the Super Admin dashboard, locked to this admin's region.
                 Its From / Until / Training fields filter the whole dashboard. -->
            @php
                $hasFilters = $filters['training_title'] || $filters['from'] || $filters['until'];
            @endphp
            <div class="space-y-6">
                <x-chart-card body-class="p-6 sm:p-8"
                    :title="__('Regional Performance & Graduates Map')"
                    :subtitle="__('Completed trainings in your region — every course in the catalog is Technical Assistance. These filters apply to the whole dashboard.')">
                    @if ($hasFilters)
                        <x-slot:action>
                            <a href="{{ route('admin.dashboard') }}" class="text-xs font-semibold text-white/70 hover:text-white transition whitespace-nowrap">
                                {{ __('Reset') }}
                            </a>
                        </x-slot:action>
                    @endif

                    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="from" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('From') }}</label>
                            <input type="date" id="from" name="from" value="{{ $filters['from'] }}" onchange="this.form.submit()"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:[color-scheme:dark] text-sm py-2.5 px-3 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                        </div>
                        <div>
                            <label for="until" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Until') }}</label>
                            <input type="date" id="until" name="until" value="{{ $filters['until'] }}" onchange="this.form.submit()"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:[color-scheme:dark] text-sm py-2.5 px-3 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="training_title" class="block text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1.5">{{ __('Training') }}</label>
                            <div class="relative">
                                <select id="training_title" name="training_title" onchange="this.form.submit()"
                                    class="appearance-none w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2.5 pl-3 pr-9 hover:border-[#152A4E] dark:hover:border-white/40 focus:border-[#152A4E] focus:ring-[#152A4E] transition">
                                    <option value="">{{ __('All Trainings') }}</option>
                                    @foreach ($trainingTitles as $title)
                                        <option value="{{ $title }}" @selected($filters['training_title'] === $title)>{{ $title }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </div>
                    </form>
                </x-chart-card>

                @include('admin.partials.performance-stat-cards', ['summary' => $monitoringSummary])

                {{-- Graduates by city, by where each graduate lives — the same map as
                     the Super Admin dashboard, with this admin's region outlined. --}}
                <x-chart-card :title="__('Graduates by Location')"
                    :subtitle="$filters['training_title']
                        ? __(':training only — :graduates across :cities.', [
                            'training' => $filters['training_title'],
                            'graduates' => trans_choice(':count graduate|:count graduates', $monitoringSummary['graduates'], ['count' => $monitoringSummary['graduates']]),
                            'cities' => trans_choice(':count city|:count cities', count($cityTotals['cities']), ['count' => count($cityTotals['cities'])]),
                        ])
                        : null"
                    body-class="pt-4">

                    @if ($monitoringSummary['trainings'] === 0)
                        <div class="mx-6 mb-6 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $hasFilters ? __('No completed trainings match these filters yet.') : __('No completed trainings in your region yet.') }}
                        </div>
                    @else
                        @include('admin.partials.city-map', ['mapId' => 'dashboardCityMap', 'cityTotals' => $cityTotals, 'onlyRegion' => $region])
                    @endif
                </x-chart-card>
            </div>

            <!-- Stat tiles -->
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    ['label' => 'Instructors on File', 'value' => $stats['instructors']],
                    ['label' => 'TNA Submissions', 'value' => $stats['tna_submissions']],
                    ['label' => 'Upcoming Trainings', 'value' => $stats['upcoming_trainings']],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm px-3 py-2.5">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __($card['label']) }}</div>
                        <div class="text-xl font-bold text-[#152A4E] dark:text-white mt-0.5">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 gap-6">
                {{-- Requests by Status — hidden per regional admin request (only super admin creates trainings now).
                To restore: add a chart-card component titled "Requests by Status", subtitled
                "All training requests filed for your region.", wrapping a h-64 max-w-xs mx-auto div
                with a canvas id="dashStatusBreakdownChart"; then widen the two-up grid below to lg:grid-cols-3. --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <x-chart-card :title="__('Graduates by Sex')" :subtitle="__('Completed trainings only.')">
                        @if ($chartData['graduatesBySex']['male'] + $chartData['graduatesBySex']['female'] > 0)
                            <div class="h-64 max-w-xs mx-auto"><canvas id="dashGraduatesBySexChart"></canvas></div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings yet.') }}</p>
                        @endif
                    </x-chart-card>
                    <x-chart-card :title="__('Graduates by Age Range')" :subtitle="__('Completed trainings only.')">
                        <div class="h-64"><canvas id="dashGraduatesByAgeRangeChart"></canvas></div>
                    </x-chart-card>
                </div>
                <x-chart-card :title="__('Most Needed Trainings')"
                    :subtitle="__('What the Training Needs Assessment says your region\'s participants need most.')">
                    @if (count($chartData['mostNeededTrainings']) > 0)
                        <div style="height: {{ max(240, count($chartData['mostNeededTrainings']) * 34) }}px"><canvas id="dashMostNeededTrainingsChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No Training Needs Assessment submissions yet.') }}</p>
                    @endif
                </x-chart-card>
                <x-chart-card :title="__('Participants by City')"
                    :subtitle="__('Every registered participant in your region, grouped by city — not affected by the filters above.')">
                    @if (count($chartData['participantsByCity']) > 0)
                        <div style="height: {{ max(240, count($chartData['participantsByCity']) * 34) }}px"><canvas id="dashParticipantsByCityChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No participants with a city on file yet.') }}</p>
                    @endif
                </x-chart-card>

                @include('admin.super-admin.partials.dashboard-atar-charts', ['chartData' => $chartData, 'chartRegionLabel' => __('your region')])

                @include('admin.partials.graduates-by-lgu')

            </div>

            {{-- Same markup the Super Admin dashboard uses — shared here rather than
                 duplicated, so the two never drift out of sync with each other again. --}}
            @include('admin.super-admin.partials.dashboard-insights')

            <!-- Module Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['title' => 'Summary', 'description' => 'Participant and training records, including status.', 'icon' => 'summary', 'route' => 'admin.summary'],
                    ['title' => 'Tools', 'description' => 'Graduates list, charts, ATAR, and evaluations.', 'icon' => 'tools', 'route' => 'admin.tools'],
                    ['title' => 'Instructors', 'description' => 'Instructor roster, deployments, and certificate codes.', 'icon' => 'instructors', 'route' => 'admin.instructors.index'],
                    ['title' => 'Training Needs Assessment', 'description' => 'Participant self-assessments and recommended trainings.', 'icon' => 'tna', 'route' => 'admin.training-needs-assessment'],
                    ['title' => 'Calendar', 'description' => 'Your region\'s scheduled trainings.', 'icon' => 'calendar', 'route' => 'admin.calendar'],
                ] as $module)
                    <a href="{{ route($module['route']) }}"
                        class="group bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-7 hover:border-[#E2762D]/50 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-4">
                            <div class="h-11 w-11 rounded-lg bg-[#E2762D]/10 dark:bg-[#E2762D]/20 flex items-center justify-center text-[#E2762D]">
                                @include('admin.partials.icon', ['name' => $module['icon']])
                            </div>
                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-[#E2762D] transition-all" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1 group-hover:text-[#E2762D] transition-colors">{{ __($module['title']) }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($module['description']) }}</p>
                    </a>
                @endforeach
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const brandNavy = '#03055A';
        const brandOrange = '#E2762D';
        const brandBlue = '#3B4FA8';
        /* Requests by Status chart — hidden per regional admin request (only super admin creates trainings now).
        To restore, uncomment this block along with its markup and canvas above.
        const statusColors = ['#94A3B8', '#3B4FA8', '#03055A', '#DC2626', '#E2762D'];

        const statusBreakdown = @json($chartData['statusBreakdown']);
        new Chart(document.getElementById('dashStatusBreakdownChart'), {
            type: 'doughnut',
            data: {
                labels: statusBreakdown.map(row => row.label),
                datasets: [{ data: statusBreakdown.map(row => row.value), backgroundColor: statusColors }],
            },
            options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
        });
        */

        const bySex = @json($chartData['graduatesBySex']);
        const sexChartEl = document.getElementById('dashGraduatesBySexChart');
        if (sexChartEl) {
            new Chart(sexChartEl, {
                type: 'doughnut',
                data: {
                    labels: ['Male', 'Female'],
                    datasets: [{ data: [bySex.male, bySex.female], backgroundColor: [brandNavy, brandOrange] }],
                },
                options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
            });
        }

        const byAge = @json($chartData['graduatesByAgeRange']);
        new Chart(document.getElementById('dashGraduatesByAgeRangeChart'), {
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

        const mostNeededTrainings = @json($chartData['mostNeededTrainings']);
        const mostNeededChartEl = document.getElementById('dashMostNeededTrainingsChart');
        if (mostNeededChartEl && mostNeededTrainings.length) {
            new Chart(mostNeededChartEl, {
                type: 'bar',
                data: {
                    labels: mostNeededTrainings.map(row => row.training),
                    datasets: [{ label: 'Recommended', data: mostNeededTrainings.map(row => row.count), backgroundColor: brandBlue, borderRadius: 4 }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }

        const participantsByCity = @json($chartData['participantsByCity']);
        const participantsByCityChartEl = document.getElementById('dashParticipantsByCityChart');
        if (participantsByCityChartEl && participantsByCity.length) {
            new Chart(participantsByCityChartEl, {
                type: 'bar',
                data: {
                    labels: participantsByCity.map(row => row.city),
                    datasets: [{ label: 'Participants', data: participantsByCity.map(row => row.total), backgroundColor: brandOrange, borderRadius: 4 }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }

        const atarByMode = @json($chartData['atarTrainingsByMode']);
        const atarModeChartEl = document.getElementById('dashAtarModeChart');
        if (atarModeChartEl && atarByMode.length) {
            new Chart(atarModeChartEl, {
                type: 'doughnut',
                data: {
                    labels: atarByMode.map(row => row.label),
                    datasets: [{ data: atarByMode.map(row => row.value), backgroundColor: [brandNavy, brandOrange, brandBlue, '#94A3B8'] }],
                },
                options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
            });
        }

        const atarByMonth = @json($chartData['atarTrainingsByMonth']);
        const atarMonthChartEl = document.getElementById('dashAtarMonthChart');
        if (atarMonthChartEl && atarByMonth.length) {
            new Chart(atarMonthChartEl, {
                type: 'bar',
                data: {
                    labels: atarByMonth.map(row => row.label),
                    datasets: [{ label: 'Trainings', data: atarByMonth.map(row => row.value), backgroundColor: brandBlue, borderRadius: 4 }],
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
            });
        }

        const atarBySector = @json($chartData['atarGraduatesBySector']);
        const atarSectorChartEl = document.getElementById('dashAtarSectorChart');
        if (atarSectorChartEl && atarBySector.length) {
            new Chart(atarSectorChartEl, {
                type: 'bar',
                data: {
                    labels: atarBySector.map(row => row.sector),
                    datasets: [{ label: 'Graduates', data: atarBySector.map(row => row.graduates), backgroundColor: brandOrange, borderRadius: 4 }],
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
