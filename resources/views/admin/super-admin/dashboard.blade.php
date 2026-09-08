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
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#152A4E] via-[#1E3A66] to-[#0D1B33] px-6 py-8 sm:px-10 sm:py-10">
                <img src="{{ asset('images/ocd-seal.png') }}" alt=""
                    class="absolute -right-10 -bottom-16 w-64 h-64 object-contain opacity-[0.08] pointer-events-none">
                <div class="relative z-10">
                    <p class="text-xs font-semibold tracking-[0.2em] text-[#E2762D] uppercase mb-2">
                        {{ __('CDTI — Central Office') }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
                        {{ __('Welcome back, :name', ['name' => explode(' ', $user->name)[0]]) }}
                    </h1>
                    <p class="text-sm text-white/70 max-w-xl">
                        {{ __('Super Admin access — full visibility across all OCD regional offices, plus admin account management.') }}
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('All training requests on file, every region.') }}</p>
                            <div class="h-64 max-w-xs mx-auto"><canvas id="dashStatusBreakdownChart"></canvas></div>
                        </div>
                        <div class="lg:pl-6">
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Sex') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings only, every region.') }}</p>
                            @if ($chartData['graduatesBySex']['male'] + $chartData['graduatesBySex']['female'] > 0)
                                <div class="h-64 max-w-xs mx-auto"><canvas id="dashGraduatesBySexChart"></canvas></div>
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No completed trainings yet.') }}</p>
                            @endif
                        </div>
                        <div class="lg:pl-6">
                            <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Age Range') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings only, every region.') }}</p>
                            <div class="h-64"><canvas id="dashGraduatesByAgeRangeChart"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
                        <h3 class="font-bold text-[#152A4E] dark:text-white">{{ __('Graduates by Training') }}</h3>
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                            @foreach (($monitoringFilters['regions'] ?? []) as $regionValue)
                                <input type="hidden" name="regions[]" value="{{ $regionValue }}">
                            @endforeach
                            @if (! empty($monitoringFilters['training_title']))
                                <input type="hidden" name="training_title" value="{{ $monitoringFilters['training_title'] }}">
                            @endif
                            @if (! empty($monitoringFilters['from']))
                                <input type="hidden" name="from" value="{{ $monitoringFilters['from'] }}">
                            @endif
                            @if (! empty($monitoringFilters['until']))
                                <input type="hidden" name="until" value="{{ $monitoringFilters['until'] }}">
                            @endif
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
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Completed trainings only — every course in the catalog is Technical Assistance.') }}</p>
                    @if (count($chartData['graduatesByTraining']) > 0)
                        <div style="height: {{ max(240, count($chartData['graduatesByTraining']) * 34) }}px"><canvas id="dashGraduatesByTrainingChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ $year === 'all' ? __('No completed trainings yet.') : __('No completed trainings for :year.', ['year' => $year]) }}</p>
                    @endif
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Most Needed Trainings') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('What the Training Needs Assessment says participants need most, across all regions.') }}</p>
                    @if (count($chartData['mostNeededTrainings']) > 0)
                        <div style="height: {{ max(240, count($chartData['mostNeededTrainings']) * 34) }}px"><canvas id="dashMostNeededTrainingsChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No Training Needs Assessment submissions yet.') }}</p>
                    @endif
                </div>
            </div>

            <!-- Regional Performance & Graduates Map (folded in from the former Regional Monitoring / Graduates Map tabs) -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Regional Performance & Graduates Map') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Every training on file is Technical Assistance. Filter by date, region, or training below.') }}</p>
                </div>

                <!-- Filter -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <x-input-label for="from" :value="__('From')" />
                            <x-text-input id="from" name="from" type="date" class="mt-1 block w-full text-sm" value="{{ $monitoringFilters['from'] }}" />
                        </div>
                        <div>
                            <x-input-label for="until" :value="__('Until')" />
                            <x-text-input id="until" name="until" type="date" class="mt-1 block w-full text-sm" value="{{ $monitoringFilters['until'] }}" />
                        </div>
                        <div>
                            <x-input-label :value="__('Regions')" />
                            <div x-data="{ open: false }" class="relative mt-1">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-2 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3 text-left hover:border-[#152A4E] dark:hover:border-white/40 transition">
                                    <span class="truncate">
                                        @if (empty($monitoringFilters['regions']))
                                            {{ __('All Regions') }}
                                        @elseif (count($monitoringFilters['regions']) === 1)
                                            {{ $monitoringFilters['regions'][0] }}
                                        @else
                                            {{ __(':count regions selected', ['count' => count($monitoringFilters['regions'])]) }}
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
                                            <input type="checkbox" name="regions[]" value="{{ $regionOption }}" @checked(in_array($regionOption, $monitoringFilters['regions']))
                                                class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                                            {{ $regionOption }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="training_title" :value="__('Training')" />
                            <select id="training_title" name="training_title" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">{{ __('All Trainings') }}</option>
                                @foreach ($trainingTitles as $title)
                                    <option value="{{ $title }}" @selected($monitoringFilters['training_title'] === $title)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-3">
                            <button type="submit" class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-4 py-2 hover:bg-[#1E3A66] transition">
                                {{ __('Apply Filters') }}
                            </button>
                            @if (! empty($monitoringFilters['regions']) || $monitoringFilters['training_title'] || $monitoringFilters['from'] || $monitoringFilters['until'])
                                <a href="{{ route('admin.dashboard') }}" class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                    {{ __('Reset') }}
                                </a>
                            @endif
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

            <div class="grid grid-cols-1 gap-6">
                <!-- Needs Assessment per LGU / Organization (moved from the Needs Assessment tab) -->
                <div id="needs-assessment-by-organization" class="scroll-mt-24 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h3 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Needs Assessment per LGU / Organization') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Training Needs Assessment submissions grouped by the participant\'s LGU or organization.') }}</p>

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

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const brandNavy = '#03055A';
        const brandOrange = '#E2762D';
        const brandBlue = '#3B4FA8';
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

    @if (! empty($mapPoints))
        <script>
            (function () {
                const points = @json($mapPoints);
                const map = L.map('dashboardGraduatesMap', { attributionControl: false, scrollWheelZoom: false }).setView([12.8797, 121.7740], 5);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
                L.control.attribution({ position: 'bottomright', prefix: false }).addAttribution('&copy; OpenStreetMap contributors').addTo(map);

                const colorFor = (agencyType) => agencyType === 'LGU' ? '#03055A' : (agencyType === 'NGA' ? '#E2762D' : '#0EA5E9');

                const bounds = [];

                const clusterGroup = L.markerClusterGroup({
                    maxClusterRadius: 45,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    iconCreateFunction: (cluster) => {
                        const count = cluster.getChildCount();
                        const size = count < 10 ? 32 : (count < 50 ? 40 : 48);
                        return L.divIcon({
                            html: '<div class="dashboard-graduates-cluster-badge" style="width:' + size + 'px;height:' + size + 'px;line-height:' + size + 'px;">' + count + '</div>',
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
                        pane: 'markerPane',
                    });

                    const subtitle = [point.agency_type, point.region].filter(Boolean).join(' · ');
                    marker.bindPopup(
                        '<div style="font-weight:700;color:#152A4E;letter-spacing:.01em;margin-bottom:2px">' + point.name + '</div>' +
                        '<div style="color:#6b7280;margin-bottom:6px">' + subtitle + '</div>' +
                        '<div style="color:#374151">' + '{{ __('Graduates') }}: <strong>' + point.graduates + '</strong></div>' +
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

                // Region boundaries as a hover-highlight backdrop underneath the point
                // markers — shaded by total graduates in that region.
                const regionNameMap = {
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

                const regionTotals = {};
                points.forEach((point) => {
                    if (!point.region) {
                        return;
                    }
                    regionTotals[point.region] ??= { graduates: 0, trainings: 0 };
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
                                const key = regionNameMap[feature.properties.REGION];
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
                                const key = regionNameMap[feature.properties.REGION] ?? feature.properties.REGION;
                                const data = regionTotals[key] || { graduates: 0, trainings: 0 };
                                const baseOpacity = opacityFor(data.graduates);
                                const baseWeight = data.graduates ? 1.5 : 1;
                                const baseColor = data.graduates ? REGION_ACCENT : REGION_BORDER_IDLE;

                                featureLayer.bindTooltip(
                                    '<div style="font-size:12px;min-width:150px">' +
                                    '<div style="font-weight:700;color:#152A4E;letter-spacing:.02em;margin-bottom:3px">' + key + '</div>' +
                                    '<div style="color:#6b7280">{{ __('Graduates') }}: <strong style="color:' + REGION_ACCENT + '">' + data.graduates + '</strong></div>' +
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
            })();
        </script>
    @endif
</x-app-layout>
