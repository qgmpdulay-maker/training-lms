<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Super Admin Dashboard') }}
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
            @include('admin.partials.welcome-banner', [
                'eyebrow' => __('CDTI — Central Office'),
                'description' => __('Super Admin access — full visibility across all OCD regional offices, plus admin account management.'),
            ])

            <!-- Everything the dashboard filters touch, re-rendered as one piece when they change -->
            <div id="dashboard-body" class="space-y-8">
                @include('admin.super-admin.partials.dashboard-body')
            </div>

            <!-- Module Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['title' => 'Summary', 'description' => 'Participant and training records across all regions and Central Office.', 'icon' => 'summary', 'route' => 'admin.summary'],
                    ['title' => 'Tools', 'description' => 'Graduates list now available; charts, ATAR, evaluations, certificates, and maps still to come.', 'icon' => 'tools', 'route' => 'admin.tools'],
                    ['title' => 'Instructors', 'description' => 'Instructor list with ratings, deployments, and certificate codes.', 'icon' => 'instructors', 'route' => 'admin.instructors.index'],
                    ['title' => 'Training Needs Assessment', 'description' => 'Participant TNA results, aggregated across all regions.', 'icon' => 'tna', 'route' => 'admin.training-needs-assessment'],
                    ['title' => 'Regional Training Calendar', 'description' => 'Color-coded by request status, all regions and Central.', 'icon' => 'calendar', 'route' => 'admin.calendar'],
                ] as $module)
                    @if ($module['route'])
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
                    @else
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-7">
                            <div class="flex items-start justify-between mb-4">
                                <div class="h-11 w-11 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    @include('admin.partials.icon', ['name' => $module['icon']])
                                </div>
                                <span class="inline-flex items-center text-[11px] font-semibold rounded-full border px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700">
                                    {{ __('Coming Soon') }}
                                </span>
                            </div>
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __($module['title']) }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($module['description']) }}</p>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Manage Users -->
            <a href="{{ route('admin.users.index') }}"
                class="rounded-xl border border-dashed border-[#152A4E]/30 dark:border-white/20 p-6 sm:p-7 flex items-center justify-between gap-4 flex-wrap hover:border-[#152A4E]/60 dark:hover:border-white/40 hover:bg-[#152A4E]/[0.02] dark:hover:bg-white/[0.02] transition">
                <div>
                    <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Manage Users') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Manage participant and admin accounts — elevate a participant to Regional Admin.') }}</p>
                </div>
                <span class="inline-flex items-center text-[11px] font-semibold rounded-full border px-2.5 py-1 bg-[#152A4E]/5 dark:bg-white/10 text-[#152A4E] dark:text-white border-[#152A4E]/20 dark:border-white/20">
                    {{ __('Open') }} &rarr;
                </span>
            </a>

        </div>
    </div>

    <script>
        // The dashboard's one filter (From / Until / Region / Training, at the
        // top of the Regional Performance section) posts here instead of doing
        // a normal GET navigation, so filtering never reloads the page or
        // resets the user's scroll position back to the top.
        function submitDashboardFilter(el, event) {
            if (event) {
                event.preventDefault();
            }
            const form = el.tagName === 'FORM' ? el : el.form;
            const params = new URLSearchParams(new FormData(form));
            fetchDashboard(form.action + '?' + params.toString());
            return false;
        }

        function submitDashboardFilterReset(event) {
            event.preventDefault();
            fetchDashboard(event.currentTarget.href);
            return false;
        }

        function fetchDashboard(url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => response.json())
                .then((data) => {
                    document.getElementById('dashboard-body').innerHTML = data.body_html;
                    renderDashboardCharts(data.chartData);
                    renderDashboardMap(data.mapPoints, data.regionDeployed);
                    window.history.pushState({}, '', url);
                })
                .catch(() => {
                    // AJAX update failed for some reason — fall back to a normal
                    // navigation so the filter still works.
                    window.location.href = url;
                });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const brandNavy = '#03055A';
        const brandOrange = '#E2762D';
        const brandBlue = '#3B4FA8';
        let dashboardChartInstances = [];

        function renderDashboardCharts(chartData) {
            dashboardChartInstances.forEach((chart) => chart.destroy());
            dashboardChartInstances = [];

            const bySex = chartData.graduatesBySex;
            const sexChartEl = document.getElementById('dashGraduatesBySexChart');
            if (sexChartEl) {
                dashboardChartInstances.push(new Chart(sexChartEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Male', 'Female'],
                        datasets: [{ data: [bySex.male, bySex.female], backgroundColor: [brandNavy, brandOrange] }],
                    },
                    options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
                }));
            }

            const byAge = chartData.graduatesByAgeRange;
            dashboardChartInstances.push(new Chart(document.getElementById('dashGraduatesByAgeRangeChart'), {
                type: 'bar',
                data: {
                    labels: ['18 - 30', '31 - 45', '46 - 59', '60 and above'],
                    datasets: [{
                        data: [byAge.age_18_30, byAge.age_31_45, byAge.age_46_59, byAge.age_60_up],
                        backgroundColor: [brandNavy, '#3B4FA8', brandOrange, '#F0A868'],
                    }],
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
            }));

            const graduatesByTraining = chartData.graduatesByTraining;
            const trainingChartEl = document.getElementById('dashGraduatesByTrainingChart');
            if (trainingChartEl && graduatesByTraining.length) {
                dashboardChartInstances.push(new Chart(trainingChartEl, {
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
                }));
            }

            const mostNeededTrainings = chartData.mostNeededTrainings;
            // Scheduled vs Completed — running totals, so the two series only
            // ever climb and the gap between them is the backlog of TA
            // trainings scheduled but not yet marked completed.
            const rva = chartData.requestedVsAccomplished || [];
            const rvaEl = document.getElementById('dashRequestedVsAccomplishedChart');
            if (rvaEl && rva.length) {
                dashboardChartInstances.push(new Chart(rvaEl, {
                    type: 'bar',
                    data: {
                        labels: rva.map(row => row.month),
                        datasets: [
                            { label: 'Scheduled (cumulative)', data: rva.map(row => row.requested), backgroundColor: brandNavy, borderRadius: 4 },
                            { label: 'Completed (cumulative)', data: rva.map(row => row.accomplished), backgroundColor: brandOrange, borderRadius: 4 },
                        ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                }));
            }

            // One chart per measure, each zero-based on its own scale. Same
            // colour per category across both, so APB is the same bar in each.
            const categoryComparison = chartData.categoryComparison || [];
            if (categoryComparison.length) {
                const categoryColours = categoryComparison.map((row, i) => i === 0 ? brandNavy : brandOrange);

                [
                    { id: 'dashCategoryTrainingsChart', key: 'trainings', axis: 'Trainings' },
                    { id: 'dashCategoryGraduatesChart', key: 'graduates', axis: 'Graduates' },
                ].forEach(function (spec) {
                    const el = document.getElementById(spec.id);
                    if (!el) {
                        return;
                    }
                    dashboardChartInstances.push(new Chart(el, {
                        type: 'bar',
                        data: {
                            labels: categoryComparison.map(row => row.label),
                            datasets: [{
                                label: spec.axis,
                                data: categoryComparison.map(row => row[spec.key]),
                                backgroundColor: categoryColours,
                                borderRadius: 4,
                            }],
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: spec.axis } } },
                        },
                    }));
                });
            }

            // One training at a time. The dropdown lives in the card header
            // and swaps the dataset in place — no refetch, since every
            // training's three-year figures are already here.
            const trend = chartData.threeYearTrend || { years: [], trainings: [] };
            const trendEl = document.getElementById('dashThreeYearTrendChart');
            const trendPicker = document.getElementById('trend_training');
            if (trendEl && trend.trainings.length) {
                const trendColours = [brandNavy, brandBlue, brandOrange];
                const trendChart = new Chart(trendEl, {
                    type: 'bar',
                    data: {
                        labels: trend.years.map(String),
                        datasets: [{
                            label: trend.trainings[0].training,
                            data: trend.trainings[0].graduates,
                            backgroundColor: trend.years.map((year, i) => trendColours[i % trendColours.length]),
                            borderRadius: 4,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { title: (items) => items[0].label } },
                        },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Graduates' } } },
                    },
                });
                dashboardChartInstances.push(trendChart);

                if (trendPicker) {
                    trendPicker.addEventListener('change', function () {
                        const row = trend.trainings[Number(this.value)];
                        if (!row) {
                            return;
                        }
                        trendChart.data.datasets[0].label = row.training;
                        trendChart.data.datasets[0].data = row.graduates;
                        trendChart.update();
                    });
                }
            }

            const mostNeededChartEl = document.getElementById('dashMostNeededTrainingsChart');
            if (mostNeededChartEl && mostNeededTrainings.length) {
                dashboardChartInstances.push(new Chart(mostNeededChartEl, {
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
                }));
            }

            const atarByMode = chartData.atarTrainingsByMode;
            const atarModeChartEl = document.getElementById('dashAtarModeChart');
            if (atarModeChartEl && atarByMode && atarByMode.length) {
                dashboardChartInstances.push(new Chart(atarModeChartEl, {
                    type: 'doughnut',
                    data: {
                        labels: atarByMode.map(row => row.label),
                        datasets: [{ data: atarByMode.map(row => row.value), backgroundColor: [brandNavy, brandOrange, brandBlue, '#94A3B8'] }],
                    },
                    options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
                }));
            }

            const atarByMonth = chartData.atarTrainingsByMonth;
            const atarMonthChartEl = document.getElementById('dashAtarMonthChart');
            if (atarMonthChartEl && atarByMonth && atarByMonth.length) {
                dashboardChartInstances.push(new Chart(atarMonthChartEl, {
                    type: 'bar',
                    data: {
                        labels: atarByMonth.map(row => row.label),
                        datasets: [{ label: 'Trainings', data: atarByMonth.map(row => row.value), backgroundColor: brandBlue, borderRadius: 4 }],
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                }));
            }

            const atarBySector = chartData.atarGraduatesBySector;
            const atarSectorChartEl = document.getElementById('dashAtarSectorChart');
            if (atarSectorChartEl && atarBySector && atarBySector.length) {
                dashboardChartInstances.push(new Chart(atarSectorChartEl, {
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
                }));
            }

        }

        renderDashboardCharts(@json($chartData));
    </script>

    <style>
        .dash-graduates-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #f6f8fc 0%, #e7ecf5 70%);
            position: relative;
            isolation: isolate;
        }
        .dark .dash-graduates-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #1c2740 0%, #131b2e 70%);
        }
        #dashboardGraduatesMap {
            background: transparent;
        }
        .dash-graduates-map-panel .leaflet-container {
            background: transparent !important;
            outline: none;
            font-family: inherit;
        }
        .dashboard-graduates-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(21, 42, 78, 0.18);
            padding: 2px;
        }
        .dashboard-graduates-popup .leaflet-popup-content {
            margin: 10px 12px;
            font-size: 12px;
            min-width: 160px;
        }
        .dashboard-graduates-popup .leaflet-popup-tip {
            box-shadow: none;
        }
        .dashboard-region-tooltip {
            background: rgba(255, 255, 255, 0.97) !important;
            border: 1px solid rgba(59, 130, 246, 0.35) !important;
            border-radius: 10px !important;
            box-shadow: 0 8px 24px rgba(21, 42, 78, 0.18) !important;
            color: #152A4E !important;
            padding: 10px 12px !important;
        }
        .dashboard-region-tooltip::before {
            display: none !important;
        }
        .dashboard-region-hover-glow {
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.85));
        }
        .dashboard-graduates-cluster-icon {
            background: transparent !important;
            border: none !important;
        }
        .dashboard-graduates-cluster-badge {
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
        let dashboardMapInstance = null;

        // Region boundaries as a hover-highlight backdrop underneath the point
        // markers — shaded by total graduates in that region.
        const dashboardRegionNameMap = {
            'Autonomous Region of Muslim Mindanao (ARMM)': 'BARMM',
            'Bicol Region (Region V)': 'Region V',
            'CALABARZON (Region IV-A)': 'Region IV-A',
            'Cagayan Valley (Region II)': 'Region II',
            'Caraga (Region XIII)': 'Region XIII',
            'Central Luzon (Region III)': 'Region III',
            'Central Visayas (Region VII)': 'Region VII',
            'Cordillera Administrative Region (CAR)': 'CAR',
            'Davao Region (Region XI)': 'Region XI',
            'Eastern Visayas (Region VIII)': 'Region VIII',
            'Ilocos Region (Region I)': 'Region I',
            'MIMAROPA (Region IV-B)': 'MIMAROPA',
            'Metropolitan Manila': 'NCR',
            'Northern Mindanao (Region X)': 'Region X',
            'SOCCSKSARGEN (Region XII)': 'Region XII',
            'Western Visayas (Region VI)': 'Region VI',
            'Zamboanga Peninsula (Region IX)': 'Region IX',
        };

        function renderDashboardMap(points, regionDeployed = {}) {
            if (dashboardMapInstance) {
                dashboardMapInstance.remove();
                dashboardMapInstance = null;
            }

            const container = document.getElementById('dashboardGraduatesMap');
            if (!container || !points || points.length === 0) {
                return;
            }

            const map = L.map(container, { attributionControl: false, scrollWheelZoom: false }).setView([12.8797, 121.7740], 5);
            dashboardMapInstance = map;

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            L.control.attribution({ position: 'bottomright', prefix: false }).addAttribution('&copy; OpenStreetMap contributors').addTo(map);

            const colorFor = (agencyType) => agencyType === 'LGU' ? '#03055A' : (agencyType === 'NGA' ? '#E2762D' : '#0EA5E9');

            const bounds = [];

            const clusterGroup = L.markerClusterGroup({
                maxClusterRadius: 45,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                // The bubble shows the graduates inside it, not how many
                // locations it groups — so with a training picked, the number on
                // the map is that training's graduates. Location count stays on
                // hover via the title.
                iconCreateFunction: (cluster) => {
                    const locations = cluster.getChildCount();
                    const graduates = cluster.getAllChildMarkers().reduce((total, m) => total + (m.options.graduates || 0), 0);
                    const label = graduates >= 1000 ? (graduates / 1000).toFixed(1).replace(/\.0$/, '') + 'k' : String(graduates);
                    const size = label.length <= 2 ? 32 : (label.length === 3 ? 40 : 48);
                    const title = graduates.toLocaleString() + ' {{ __('graduates') }} · ' + locations + ' {{ __('locations') }}';
                    return L.divIcon({
                        html: '<div class="dashboard-graduates-cluster-badge" title="' + title + '" style="width:' + size + 'px;height:' + size + 'px;line-height:' + size + 'px;">' + label + '</div>',
                        className: 'dashboard-graduates-cluster-icon',
                        iconSize: L.point(size, size),
                    });
                },
            });

            points.forEach((point) => {
                const radius = Math.max(6, Math.min(30, 5 + Math.sqrt(point.graduates)));
                const marker = L.circleMarker([point.latitude, point.longitude], {
                    radius,
                    color: '#fff',
                    weight: 1.5,
                    fillColor: colorFor(point.agency_type),
                    fillOpacity: 0.85,
                    graduates: point.graduates,
                    pane: 'markerPane',
                });

                const subtitle = [point.agency_type, point.region].filter(Boolean).join(' · ');
                marker.bindPopup(
                    '<div style="font-weight:700;color:#152A4E;letter-spacing:.01em;margin-bottom:2px">' + point.name + '</div>' +
                    '<div style="color:#6b7280;margin-bottom:6px">' + subtitle + '</div>' +
                    '<div style="color:#374151">' + '{{ __('Graduates') }}: <strong>' + point.graduates + '</strong></div>' +
                    '<div style="color:#374151">' + '{{ __('Deployed') }}: <strong>' + point.deployed + '</strong></div>' +
                    '<div style="color:#374151">' + '{{ __('Teams organized') }}: <strong>' + point.teams + '</strong></div>' +
                    '<div style="color:#374151">' + '{{ __('Trainings') }}: <strong>' + point.trainings + '</strong></div>',
                    { className: 'dashboard-graduates-popup', closeButton: false }
                );

                marker.on('mouseover', function () { this.openPopup(); });
                marker.on('mouseout', function () { this.closePopup(); });

                clusterGroup.addLayer(marker);
                bounds.push([point.latitude, point.longitude]);
            });

            map.addLayer(clusterGroup);

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 9 });
            }

            const regionTotals = {};
            points.forEach((point) => {
                if (!point.region) {
                    return;
                }
                regionTotals[point.region] ??= { graduates: 0, trainings: 0, deployed: regionDeployed[point.region] ?? 0 };
                regionTotals[point.region].graduates += point.graduates;
                regionTotals[point.region].trainings += point.trainings;
            });

            const REGION_ACCENT = '#3B82F6';
            const REGION_BORDER_IDLE = 'rgba(21, 42, 78, 0.22)';
            const opacityScale = [0.22, 0.4, 0.55, 0.75];
            const maxRegionGraduates = Math.max(1, ...Object.values(regionTotals).map((r) => r.graduates), 1);

            const opacityFor = (graduates) => {
                if (!graduates) {
                    return 0;
                }
                const step = Math.min(opacityScale.length - 1, Math.floor((graduates / maxRegionGraduates) * opacityScale.length));
                return opacityScale[step];
            };

            fetch('https://cdn.jsdelivr.net/gh/macoymejia/geojsonph@master/Regions/Regions.bit.json')
                .then((response) => response.json())
                .then((geojson) => {
                    const layer = L.geoJSON(geojson, {
                        style: (feature) => {
                            const key = dashboardRegionNameMap[feature.properties.REGION];
                            const data = regionTotals[key];
                            const active = !!(data && data.graduates);

                            return {
                                fillColor: REGION_ACCENT,
                                fillOpacity: opacityFor(data ? data.graduates : 0),
                                color: active ? REGION_ACCENT : REGION_BORDER_IDLE,
                                weight: active ? 1.5 : 1,
                            };
                        },
                        onEachFeature: (feature, featureLayer) => {
                            const key = dashboardRegionNameMap[feature.properties.REGION] ?? feature.properties.REGION;
                            const data = regionTotals[key] || { graduates: 0, trainings: 0, deployed: 0 };
                            const baseOpacity = opacityFor(data.graduates);
                            const baseWeight = data.graduates ? 1.5 : 1;
                            const baseColor = data.graduates ? REGION_ACCENT : REGION_BORDER_IDLE;

                            featureLayer.bindTooltip(
                                '<div style="font-size:12px;min-width:150px">' +
                                '<div style="font-weight:700;color:#152A4E;letter-spacing:.02em;margin-bottom:3px">' + key + '</div>' +
                                '<div style="color:#6b7280">{{ __('Graduates') }}: <strong style="color:' + REGION_ACCENT + '">' + data.graduates + '</strong></div>' +
                                '<div style="color:#6b7280">{{ __('Deployed') }}: <strong style="color:' + REGION_ACCENT + '">' + data.deployed + '</strong></div>' +
                                '<div style="color:#6b7280">{{ __('Trainings') }}: <strong style="color:' + REGION_ACCENT + '">' + data.trainings + '</strong></div>' +
                                '</div>',
                                { sticky: true, className: 'dashboard-region-tooltip', direction: 'top' }
                            );

                            featureLayer.on('mouseover', function () {
                                this.setStyle({ fillOpacity: Math.min(0.9, baseOpacity + 0.3), weight: 2.5, color: REGION_ACCENT });
                                this.bringToFront();
                                const el = this.getElement();
                                if (el) el.classList.add('dashboard-region-hover-glow');
                            });
                            featureLayer.on('mouseout', function () {
                                this.setStyle({ fillOpacity: baseOpacity, weight: baseWeight, color: baseColor });
                                const el = this.getElement();
                                if (el) el.classList.remove('dashboard-region-hover-glow');
                            });
                        },
                    }).addTo(map);

                    layer.bringToBack();
                })
                .catch(() => {});

            setTimeout(() => map.invalidateSize(), 200);
        }

        renderDashboardMap(@json($mapPoints), @json((object) $regionDeployed));
    </script>
</x-app-layout>
