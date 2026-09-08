<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Regional Dashboard') }}
        </h2>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] px-6 py-8 sm:px-10 sm:py-10">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-10 -bottom-16 w-64 h-64 object-contain opacity-[0.08] pointer-events-none">
                <div class="relative z-10">
                    <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">
                        {{ __('OCD Regional Office') }}{{ $user->region ? ' — '.$user->region : '' }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                        {{ __('Welcome back, :name', ['name' => explode(' ', $user->name)[0]]) }}
                    </h1>
                    <p class="text-sm text-white/70 max-w-xl">
                        {{ __('Regional admin access — manage your region\'s trainings, instructors, and reports below.') }}
                    </p>
                </div>
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
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:divide-x lg:divide-gray-100 dark:lg:divide-gray-700">
                        <div>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Requests by Status') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('All training requests filed for your region.') }}</p>
                            <div class="h-64 max-w-xs mx-auto"><canvas id="dashStatusBreakdownChart"></canvas></div>
                        </div>
                        <div class="lg:pl-6">
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Sex') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings only.') }}</p>
                            @if ($chartData['graduatesBySex']['male'] + $chartData['graduatesBySex']['female'] > 0)
                                <div class="h-64 max-w-xs mx-auto"><canvas id="dashGraduatesBySexChart"></canvas></div>
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings yet.') }}</p>
                            @endif
                        </div>
                        <div class="lg:pl-6">
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Age Range') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings only.') }}</p>
                            <div class="h-64"><canvas id="dashGraduatesByAgeRangeChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                        <h3 class="font-bold text-[#152A4E] dark:text-white">{{ __('Graduates by Training') }}</h3>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                            <label for="year" class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Year') }}</label>
                            <select id="year" name="year" onchange="this.form.submit()"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                                @foreach ($availableYears as $yearOption)
                                    <option value="{{ $yearOption }}" @selected((string) $year === (string) $yearOption)>{{ $yearOption }}</option>
                                @endforeach
                                <option value="all" @selected($year === 'all')>{{ __('All Years') }}</option>
                            </select>
                        </form>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings in your region — every course in the catalog is Technical Assistance.') }}</p>
                    @if (count($chartData['graduatesByTraining']) > 0)
                        <div style="height: {{ max(240, count($chartData['graduatesByTraining']) * 34) }}px"><canvas id="dashGraduatesByTrainingChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ $year === 'all' ? __('No completed trainings yet.') : __('No completed trainings for :year.', ['year' => $year]) }}</p>
                    @endif
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Most Needed Trainings') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('What the Training Needs Assessment says your region\'s participants need most.') }}</p>
                    @if (count($chartData['mostNeededTrainings']) > 0)
                        <div style="height: {{ max(240, count($chartData['mostNeededTrainings']) * 34) }}px"><canvas id="dashMostNeededTrainingsChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No Training Needs Assessment submissions yet.') }}</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between flex-wrap gap-3 px-6 pt-6 pb-4">
                        <div>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Location') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Completed trainings across your region, plotted by LGU / NGA — marker size scales with graduate count.') }}</p>
                        </div>
                        <a href="{{ route('admin.monitoring.map') }}"
                            class="shrink-0 inline-flex items-center gap-1 text-xs font-semibold text-[#152A4E] dark:text-white hover:text-[#E2762D] dark:hover:text-[#E2762D] transition whitespace-nowrap">
                            {{ __('View full map & filters') }}
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>

                    @if (count($graduatesByLocation) === 0)
                        <div class="mx-6 mb-6 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No completed trainings in your region yet.') }}
                        </div>
                    @else
                        <div class="dash-graduates-map-panel px-3">
                            <div id="dashGraduatesMap" style="height: 360px; border-radius: 0.5rem;"></div>
                        </div>

                        <div class="overflow-x-auto p-6 pt-4">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        <th class="py-2 pr-4">{{ __('LGU / NGA') }}</th>
                                        <th class="py-2 pr-4">{{ __('Trainings') }}</th>
                                        <th class="py-2 pr-4">{{ __('Graduates') }}</th>
                                        <th class="py-2 pr-4">{{ __('Teams') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach (array_slice($graduatesByLocation, 0, 10) as $point)
                                        @php
                                            $dotColor = $point['agency_type'] === 'LGU' ? '#03055A' : ($point['agency_type'] === 'NGA' ? '#E2762D' : '#0EA5E9');
                                        @endphp
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
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['trainings'] }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['graduates'] }}</td>
                                            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['teams'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (count($graduatesByLocation) > 10)
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">{{ __('Top 10 of :count locations, by graduate count.', ['count' => count($graduatesByLocation)]) }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                @include('admin.partials.graduates-by-lgu')

            </div>

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

    <style>
        .dash-graduates-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #f6f8fc 0%, #e7ecf5 70%);
            position: relative;
            isolation: isolate;
        }
        .dark .dash-graduates-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #1c2740 0%, #131b2e 70%);
        }
        #dashGraduatesMap {
            background: transparent;
        }
        .dash-graduates-map-panel .leaflet-container {
            background: transparent !important;
            outline: none;
            font-family: inherit;
        }
        .dash-graduates-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(21, 42, 78, 0.18);
            padding: 2px;
        }
        .dash-graduates-popup .leaflet-popup-content {
            margin: 10px 12px;
            font-size: 12px;
            min-width: 160px;
        }
        .dash-graduates-popup .leaflet-popup-tip {
            box-shadow: none;
        }
        .dash-graduates-cluster-icon {
            background: transparent !important;
            border: none !important;
        }
        .dash-graduates-cluster-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: rgba(21, 42, 78, 0.88);
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(21, 42, 78, 0.35), 0 0 0 3px rgba(255, 255, 255, 0.85);
        }
    </style>

    <script>
        (function () {
            const points = @json($graduatesByLocation);
            const center = @json($regionCenter);
            const map = L.map('dashGraduatesMap', { attributionControl: false, scrollWheelZoom: false }).setView(center, 8);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            L.control.attribution({ position: 'bottomright', prefix: false }).addAttribution('&copy; OpenStreetMap contributors').addTo(map);

            const colorFor = (agencyType) => agencyType === 'LGU' ? '#03055A' : (agencyType === 'NGA' ? '#E2762D' : '#0EA5E9');

            const clusterGroup = L.markerClusterGroup({
                maxClusterRadius: 45,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                iconCreateFunction: (cluster) => {
                    const count = cluster.getChildCount();
                    const size = count < 10 ? 30 : (count < 50 ? 38 : 46);
                    return L.divIcon({
                        html: '<div class="dash-graduates-cluster-badge" style="width:' + size + 'px;height:' + size + 'px;line-height:' + size + 'px;">' + count + '</div>',
                        className: 'dash-graduates-cluster-icon',
                        iconSize: L.point(size, size),
                    });
                },
            });

            const bounds = [];
            points.forEach((point) => {
                const radius = Math.max(6, Math.min(26, 5 + Math.sqrt(point.graduates)));
                const marker = L.circleMarker([point.latitude, point.longitude], {
                    radius,
                    color: '#fff',
                    weight: 1.5,
                    fillColor: colorFor(point.agency_type),
                    fillOpacity: 0.85,
                });

                const subtitle = [point.agency_type, point.region].filter(Boolean).join(' · ');
                marker.bindPopup(
                    '<div style="font-weight:700;color:#152A4E;letter-spacing:.01em;margin-bottom:2px">' + point.name + '</div>' +
                    '<div style="color:#6b7280;margin-bottom:6px">' + subtitle + '</div>' +
                    '<div style="color:#374151">{{ __('Graduates') }}: <strong>' + point.graduates + '</strong></div>' +
                    '<div style="color:#374151">{{ __('Teams organized') }}: <strong>' + point.teams + '</strong></div>' +
                    '<div style="color:#374151">{{ __('Trainings') }}: <strong>' + point.trainings + '</strong></div>',
                    { className: 'dash-graduates-popup', closeButton: false }
                );

                marker.on('mouseover', function () { this.openPopup(); });
                marker.on('mouseout', function () { this.closePopup(); });

                clusterGroup.addLayer(marker);
                bounds.push([point.latitude, point.longitude]);
            });

            map.addLayer(clusterGroup);

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30], maxZoom: 11 });
            }

            setTimeout(() => map.invalidateSize(), 200);
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const brandNavy = '#03055A';
        const brandOrange = '#E2762D';
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

        const graduatesByTraining = @json($chartData['graduatesByTraining']);
        const trainingChartEl = document.getElementById('dashGraduatesByTrainingChart');
        if (trainingChartEl && graduatesByTraining.length) {
            new Chart(trainingChartEl, {
                type: 'bar',
                data: {
                    labels: graduatesByTraining.map(row => row.training),
                    datasets: [{ label: 'Graduates', data: graduatesByTraining.map(row => row.graduates), backgroundColor: brandOrange, borderRadius: 4 }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        }

        const mostNeededTrainings = @json($chartData['mostNeededTrainings']);
        const mostNeededChartEl = document.getElementById('dashMostNeededTrainingsChart');
        if (mostNeededChartEl && mostNeededTrainings.length) {
            new Chart(mostNeededChartEl, {
                type: 'bar',
                data: {
                    labels: mostNeededTrainings.map(row => row.training),
                    datasets: [{ label: 'Recommended', data: mostNeededTrainings.map(row => row.count), backgroundColor: brandOrange, borderRadius: 4 }],
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
