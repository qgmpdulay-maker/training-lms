{{--
    Every part of the dashboard the filters touch: the Regional
    Performance section (which holds the filters themselves), the stat tiles,
    the charts, the Needs Assessment table and Graduates by City card, and the
    insights. Returned whole by DashboardController::index() on each filter
    change so they all stay in step — see fetchDashboard() in
    index.blade.php.
--}}
<!-- Regional Performance & Graduates Map — first thing under the welcome banner -->
<div id="dashboard-monitoring">
    @include('admin.dashboard.performance')
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
<div id="dashboard-charts">
    @include('admin.dashboard.charts')
</div>

{{-- The "Needs Assessment per LGU / Organization" table used to sit here.
     Removed 2026-10-19: TNA belongs on the Needs Assessment tab, and a second
     copy on the dashboard was one more thing to read past. The dashboard
     keeps the "Most Needed Trainings" chart, which answers the same question
     at a glance. --}}
<div class="grid grid-cols-1 gap-6">
    @include('admin.partials.graduates-by-city')
</div>

<div id="dashboard-insights">
    @include('admin.dashboard.insights')
</div>
