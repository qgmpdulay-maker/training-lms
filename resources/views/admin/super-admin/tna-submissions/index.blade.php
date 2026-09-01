<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Training Needs Assessment — Organization Submissions') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Read-only copies of the formal TNA reports regional admins have logged for LGUs and NGAs in their region.') }}</p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.tna-submissions.form') }}" target="_blank"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border border-gray-200 dark:border-gray-600 text-[#152A4E] dark:text-white px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        {{ __('Download Blank Form') }}
                    </a>
                    <a href="{{ route('admin.tna-submissions.per-organization', request()->query()) }}"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold rounded-md bg-[#152A4E] text-white px-3 py-2 hover:bg-[#1E3A66] transition">
                        {{ __('Per Organization Breakdown') }}
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                <form method="GET" action="{{ route('admin.tna-submissions.index') }}" class="flex items-center gap-2">
                    @foreach (['agency_type', 'status', 'from', 'until'] as $carry)
                        @if (($filters[$carry] ?? null))
                            <input type="hidden" name="{{ $carry }}" value="{{ $filters[$carry] }}">
                        @endif
                    @endforeach
                    <select name="region" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                        <option value="">{{ __('All Regions (Philippines)') }}</option>
                        @foreach ($regions as $regionOption)
                            <option value="{{ $regionOption }}" @selected($selectedRegion === $regionOption)>{{ $regionOption }}</option>
                        @endforeach
                    </select>
                </form>

                <form method="GET" action="{{ route('admin.tna-submissions.index') }}" class="flex items-center gap-2">
                    @if ($selectedRegion)
                        <input type="hidden" name="region" value="{{ $selectedRegion }}">
                    @endif
                    <select name="agency_type" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5">
                        <option value="">{{ __('LGU / NGA') }}</option>
                        @foreach ($agencyTypeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['agency_type'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5">
                        <option value="">{{ __('Any Status') }}</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                @if ($selectedRegion || ($filters['agency_type'] ?? null) || ($filters['status'] ?? null))
                    <a href="{{ route('admin.tna-submissions.index') }}"
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                        {{ __('Reset filters') }}
                    </a>
                @endif
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('TNA Submissions per Region') }}</h3>
                    <div class="h-64"><canvas id="submissionsByRegionChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-[#152A4E] dark:text-white mb-3">{{ __('Review Status') }}</h3>
                    <div class="h-64"><canvas id="statusChart"></canvas></div>
                </div>
            </div>

            <!-- Submissions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Submitted Copies') }}</h2>

                @if ($submissions->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No TNA submissions match the current filters.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Region') }}</th>
                                    <th class="py-2 pr-4">{{ __('LGU / Organization') }}</th>
                                    <th class="py-2 pr-4">{{ __('Topic') }}</th>
                                    <th class="py-2 pr-4">{{ __('Personnel') }}</th>
                                    <th class="py-2 pr-4">{{ __('Submitted By') }}</th>
                                    <th class="py-2 pr-4">{{ __('Date Assessed') }}</th>
                                    <th class="py-2 pr-4">{{ __('Copy') }}</th>
                                    <th class="py-2 pr-4">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($submissions as $submission)
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700">
                                                {{ $submission->region }}
                                            </span>
                                        </td>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-[#152A4E] dark:text-white">{{ $submission->organization ?? '—' }}</div>
                                            @if ($submission->agencyTypeLabel())
                                                <div class="text-xs text-gray-400">{{ $submission->agencyTypeLabel() }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->training_topic }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->personnel_assessed }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->submitted_by }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $submission->date_assessed->format('M j, Y') }}</td>
                                        <td class="py-3 pr-4">
                                            @if ($submission->hasResultsPdf())
                                                <a href="{{ asset('storage/'.$submission->results_pdf_path) }}" target="_blank"
                                                    class="text-xs font-semibold text-green-700 dark:text-green-400 hover:underline">{{ __('View PDF') }}</a>
                                            @else
                                                <span class="text-xs text-gray-400">{{ __('Not uploaded yet') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            <span @class([
                                                'inline-flex items-center text-xs font-semibold rounded-full border px-2.5 py-1',
                                                'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600' => $submission->status === \App\Models\TnaSubmission::STATUS_PENDING,
                                                'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700' => $submission->status === \App\Models\TnaSubmission::STATUS_REVIEWED,
                                            ])>
                                                {{ $submission->statusLabel() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const brandNavy = '#03055A';
        const brandOrange = '#E2762D';

        const byRegion = @json($chartData['byRegion']);
        new Chart(document.getElementById('submissionsByRegionChart'), {
            type: 'bar',
            data: {
                labels: byRegion.map(row => row.region),
                datasets: [{ label: 'Submissions', data: byRegion.map(row => row.count), backgroundColor: brandNavy }],
            },
            options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } },
        });

        const byStatus = @json($chartData['byStatus']);
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Reviewed'],
                datasets: [{ data: [byStatus.pending, byStatus.reviewed], backgroundColor: [brandOrange, brandNavy] }],
            },
            options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
        });
    </script>
</x-app-layout>
