<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Super Admin Dashboard') }}
        </h2>
    </x-slot>

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
                    if (window.renderCityMaps) {
                        window.renderCityMaps();
                    } else if (document.querySelector('[data-city-map]')) {
                        // The page first loaded with nothing to map, so the
                        // map script never loaded — fetch the page for real.
                        window.location.href = url;
                        return;
                    }
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

        // Three-Year Trend line colours: the first six slots of a
        // colour-blind-checked categorical palette, stepped separately for the
        // light and dark card surfaces (white / gray-800). Each slot also has
        // its own marker shape, so colour is never the only way to tell two
        // lines apart. Six is also the cap on how many can be compared at once.
        const TREND_COLOURS_LIGHT = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300'];
        const TREND_COLOURS_DARK = ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300'];
        const TREND_MARKERS = [
            { style: 'circle', rotation: 0 },
            { style: 'rect', rotation: 0 },
            { style: 'triangle', rotation: 0 },
            { style: 'rectRot', rotation: 0 },
            { style: 'triangle', rotation: 180 },
            { style: 'rectRounded', rotation: 0 },
        ];
        const TREND_MAX = TREND_COLOURS_LIGHT.length;
        // Training names currently compared, kept across filter changes.
        let trendSelection = null;

        // One row of the Three-Year Trend "View as table" — built from DOM
        // nodes rather than an HTML string, since training names are data.
        function trendTableRow(row, colour) {
            const tr = document.createElement('tr');
            const name = document.createElement('td');
            name.className = 'py-2 pr-4 text-gray-700 dark:text-gray-200';
            const swatch = document.createElement('span');
            swatch.className = 'inline-block w-2.5 h-2.5 rounded-full mr-2 align-middle';
            swatch.style.background = colour;
            name.append(swatch, row.training);
            tr.append(name, ...row.graduates.map((value) => {
                const td = document.createElement('td');
                td.className = 'py-2 pr-4 text-right tabular-nums text-gray-600 dark:text-gray-300';
                td.textContent = value;
                return td;
            }));
            return tr;
        }

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

            // Three-Year Trend: a line per ticked training. A training keeps
            // its colour and marker for as long as it stays ticked, so
            // unticking one never repaints the others.
            const trend = chartData.threeYearTrend || { years: [], trainings: [] };
            const trendEl = document.getElementById('dashThreeYearTrendChart');
            if (trendEl && trend.trainings.length) {
                const dark = document.documentElement.classList.contains('dark');
                const colours = dark ? TREND_COLOURS_DARK : TREND_COLOURS_LIGHT;
                const surface = dark ? '#1f2937' : '#ffffff';
                const boxes = [...document.querySelectorAll('.trend-training-option')];
                const label = document.getElementById('trendSelectionLabel');
                const table = document.getElementById('dashThreeYearTrendTable');

                // Changing a dashboard filter re-renders this card; tick the
                // same trainings again where they're still in the results.
                if (boxes.length && trendSelection) {
                    const kept = boxes.filter((box) => trendSelection.includes(trend.trainings[Number(box.value)].training));
                    if (kept.length) {
                        boxes.forEach((box) => { box.checked = kept.includes(box); });
                    }
                }

                // With the Training filter set there's no checklist, just the one training.
                const picked = () => boxes.length
                    ? boxes.filter((box) => box.checked).map((box) => Number(box.value))
                    : [0];

                const slotOf = new Map();
                const assignSlots = (indexes) => {
                    [...slotOf.keys()].forEach((i) => {
                        if (!indexes.includes(i)) {
                            slotOf.delete(i);
                        }
                    });
                    indexes.forEach((i) => {
                        if (!slotOf.has(i)) {
                            const used = new Set(slotOf.values());
                            slotOf.set(i, colours.findIndex((colour, slot) => !used.has(slot)));
                        }
                    });
                };

                const datasetFor = (i) => {
                    const slot = slotOf.get(i);
                    return {
                        label: trend.trainings[i].training,
                        data: trend.trainings[i].graduates,
                        borderColor: colours[slot],
                        backgroundColor: colours[slot],
                        borderWidth: 2,
                        borderCapStyle: 'round',
                        borderJoinStyle: 'round',
                        tension: 0,
                        pointStyle: TREND_MARKERS[slot].style,
                        pointRotation: TREND_MARKERS[slot].rotation,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBorderWidth: 2,
                        pointBorderColor: surface,
                        pointHoverBorderColor: surface,
                    };
                };

                // A faint vertical guide at the year being hovered.
                const crosshair = {
                    id: 'trendCrosshair',
                    beforeDatasetsDraw(chart) {
                        const active = chart.tooltip ? chart.tooltip.getActiveElements() : [];
                        if (!active.length) {
                            return;
                        }
                        const { ctx, chartArea } = chart;
                        ctx.save();
                        ctx.strokeStyle = dark ? 'rgba(255, 255, 255, 0.25)' : 'rgba(21, 42, 78, 0.2)';
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(active[0].element.x, chartArea.top);
                        ctx.lineTo(active[0].element.x, chartArea.bottom);
                        ctx.stroke();
                        ctx.restore();
                    },
                };

                const trendChart = new Chart(trendEl, {
                    type: 'line',
                    data: { labels: trend.years.map(String), datasets: [] },
                    options: {
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8, padding: 16 } },
                            tooltip: { usePointStyle: true, itemSort: (a, b) => b.parsed.y - a.parsed.y },
                        },
                        scales: {
                            x: { offset: true, grid: { display: false } },
                            y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Graduates' } },
                        },
                    },
                    plugins: [crosshair],
                });
                dashboardChartInstances.push(trendChart);

                const sync = () => {
                    const indexes = picked();
                    assignSlots(indexes);
                    trendChart.data.datasets = indexes.map(datasetFor);
                    // One line needs no legend — the card title names it.
                    trendChart.options.plugins.legend.display = indexes.length > 1;
                    trendChart.update();

                    boxes.forEach((box) => { box.disabled = !box.checked && indexes.length >= TREND_MAX; });
                    if (label) {
                        label.textContent = indexes.length === 1 ? '1 training' : indexes.length + ' trainings';
                    }
                    if (boxes.length) {
                        trendSelection = indexes.map((i) => trend.trainings[i].training);
                    }
                    if (table) {
                        table.replaceChildren(...indexes.map((i) => trendTableRow(trend.trainings[i], colours[slotOf.get(i)])));
                    }
                };

                boxes.forEach((box) => box.addEventListener('change', () => {
                    // Always keep at least one line on the chart.
                    if (!box.checked && picked().length === 0) {
                        box.checked = true;
                        return;
                    }
                    sync();
                }));
                sync();
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
</x-app-layout>
