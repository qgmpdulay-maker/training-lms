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

<div class="grid grid-cols-1 gap-6">
    <!-- Needs Assessment per LGU / Organization (moved from the Needs Assessment tab) -->
    <x-chart-card id="needs-assessment-by-organization" class="scroll-mt-24"
        body-class="p-6 sm:p-8"
        :title="__('Needs Assessment per LGU / Organization')">

        @if ($needsAssessmentByOrganization->isEmpty())
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                {{ __('No Training Needs Assessment submissions yet.') }}
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <th class="py-2 pr-4">{{ __('LGU / Organization') }}</th>
                            <th class="py-2 pr-4">{{ __('Region') }}</th>
                            <th class="py-2 pr-4">{{ __('Submissions') }}</th>
                            <th class="py-2 pr-4">{{ __('Most Needed Training') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($needsAssessmentByOrganization as $row)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $row['organization'] }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['region'] }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['submissions'] }}</td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['top_training'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $needsAssessmentByOrganization->links() }}
            </div>
        @endif
    </x-chart-card>

    @include('admin.partials.graduates-by-city')
</div>

<div id="dashboard-insights">
    @include('admin.dashboard.insights')
</div>
