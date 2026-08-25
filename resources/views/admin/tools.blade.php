<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tools') }}
        </h2>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="flex items-start gap-3 text-sm text-green-800 dark:text-green-300 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($regionLocked)
                <div class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ __('Showing :region only. Requests need to be tagged with a region on the Summary tab to show up here.', ['region' => $region]) }}</span>
                </div>
            @else
                <div class="flex items-center flex-wrap gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm px-5 py-3">
                    <span class="text-sm font-semibold text-[#152A4E] dark:text-white">{{ __('Region') }}</span>
                    <form method="GET" action="{{ route('admin.tools') }}" class="flex items-center gap-2">
                        <select name="region" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5 focus:border-[#152A4E] focus:ring-[#152A4E]">
                            <option value="">{{ __('All Regions (Philippines)') }}</option>
                            @foreach ($regions as $regionOption)
                                <option value="{{ $regionOption }}" @selected($region === $regionOption)>{{ $regionOption }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($region)
                        <a href="{{ route('admin.tools') }}"
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                            {{ __('Reset to all regions') }}
                        </a>
                    @endif
                </div>
            @endif

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Training Status (Accomplished vs Pending) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Status') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Share of training requests completed vs. still in progress.') }}</p>

                    <div class="flex items-center gap-8">
                        <div class="relative w-40 h-40 shrink-0">
                            <svg viewBox="0 0 100 100" class="w-40 h-40 -rotate-90">
                                @if ($statusDonut['total'] === 0)
                                    <circle cx="50" cy="50" r="{{ $statusDonut['radius'] }}" fill="none" stroke="#e1e0d9" stroke-width="14" />
                                @else
                                    @foreach ($statusDonut['segments'] as $segment)
                                        @if ($segment['value'] > 0)
                                            <circle cx="50" cy="50" r="{{ $statusDonut['radius'] }}" fill="none"
                                                stroke="{{ $segment['color'] }}" stroke-width="14"
                                                stroke-dasharray="{{ $segment['dasharray'] }}"
                                                stroke-dashoffset="{{ $segment['dashoffset'] }}">
                                                <title>{{ $segment['label'] }}: {{ $segment['value'] }} ({{ $segment['percent'] }}%)</title>
                                            </circle>
                                        @endif
                                    @endforeach
                                @endif
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-[#152A4E] dark:text-white">{{ $statusDonut['total'] }}</span>
                                <span class="text-[11px] text-gray-400 uppercase tracking-wide">{{ __('Total') }}</span>
                            </div>
                        </div>

                        <ul class="space-y-2.5 text-sm">
                            @if ($statusDonut['total'] === 0)
                                <li class="text-gray-400">{{ __('No training requests on record yet.') }}</li>
                            @else
                                @foreach ($statusDonut['segments'] as $segment)
                                    <li class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: {{ $segment['color'] }};"></span>
                                        <span class="text-gray-600 dark:text-gray-300">{{ $segment['label'] }}</span>
                                        <span class="font-semibold text-[#152A4E] dark:text-white tabular-nums">{{ $segment['value'] }}</span>
                                        <span class="text-gray-400 text-xs">({{ $segment['percent'] }}%)</span>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Requests by Status') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Every training request, broken down by current status.') }}</p>

                    <div class="space-y-3">
                        @foreach ($statusBars as $bar)
                            <div class="flex items-center gap-3">
                                <div class="w-32 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300">{{ $bar['label'] }}</div>
                                <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full transition-all" style="width: {{ $bar['percent'] }}%; background-color: {{ $bar['color'] }};"></div>
                                </div>
                                <div class="w-8 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ $bar['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Graduates per training -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates per Training') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('Auto-generated from completed training requests, broken down by year.') }}</p>

                @if ($graduatesByTraining->isEmpty())
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed trainings on record yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Training') }}</th>
                                    <th class="py-2 pr-4">{{ __('Total Graduates') }}</th>
                                    <th class="py-2 pr-4">{{ __('By Year') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($graduatesByTraining as $trainingTitle => $data)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $trainingTitle }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $data['total'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">
                                            @foreach ($data['byYear'] as $year => $count)
                                                <span class="inline-flex items-center text-xs font-semibold rounded-full border px-2 py-0.5 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 mr-1">
                                                    {{ $year }}: {{ $count }}
                                                </span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Certificates & ATAR -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Certificates & ATAR') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __("Upload a certificate (shown on the participant's own dashboard) and/or the After Training Activity Report per record. No downloadable templates yet — those need OCD's actual template files — and files are stored on the app's own storage, not Google Drive.") }}
                </p>

                <div id="files-section">
                    @include('admin.partials.files-table')
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const container = document.getElementById('files-section');

                    container.addEventListener('click', function (event) {
                        const link = event.target.closest('.files-pagination a');
                        if (!link || !link.href) {
                            return;
                        }

                        event.preventDefault();

                        fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then((response) => response.text())
                            .then((html) => {
                                container.innerHTML = html;
                                window.history.replaceState({}, '', link.href);
                            });
                    });
                });
            </script>

            <!-- Evaluation Computation (L1 / L2) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Evaluation Computation (L1 / L2)') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Auto-computed from evaluations entered per training request — use "Add Evaluation" in the table above to enter results. Pick a training below, then expand a session to see its L1 and L2 results.') }}
                </p>

                @if (empty($evaluationsByTraining))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No evaluations entered yet.') }}
                    </div>
                @else
                    <div x-data="{ activeTraining: @js(array_key_first($evaluationsByTraining)) }">
                        <div class="flex items-center gap-1 overflow-x-auto bg-gray-100 dark:bg-gray-900/40 rounded-xl p-1.5">
                            @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                                <button type="button" @click="activeTraining = @js($trainingTitle)"
                                    :class="activeTraining === @js($trainingTitle)
                                        ? 'bg-white dark:bg-gray-700 text-[#152A4E] dark:text-white shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition whitespace-nowrap">
                                    {{ $trainingTitle }}
                                    <span :class="activeTraining === @js($trainingTitle)
                                            ? 'bg-[#152A4E]/10 text-[#152A4E] dark:bg-white/15 dark:text-white px-1.5 py-0.5 rounded-full text-xs font-semibold'
                                            : 'text-gray-400 dark:text-gray-500 text-xs font-normal'">
                                        {{ $sessions->count() }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($evaluationsByTraining as $trainingTitle => $sessions)
                            <div x-show="activeTraining === @js($trainingTitle)" x-cloak class="mt-5">
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($sessions as $session)
                                        <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                                            <button type="button" @click="open = !open"
                                                class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <svg class="w-4 h-4 shrink-0 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                    </svg>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-[#152A4E] dark:text-white truncate">{{ $session['venue'] }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $session['preferred_date']->format('M j, Y') }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0">
                                                    @if ($session['overall_trainer_rating'])
                                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.958c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.958a1 1 0 00-.364-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z" /></svg>
                                                            {{ $session['overall_trainer_rating'] }}
                                                        </span>
                                                    @endif
                                                    <span class="hidden sm:inline text-xs text-gray-400" title="{{ $session['updated_at']?->format('M j, Y g:i A') }}">
                                                        {{ __('Updated') }} {{ $session['updated_at']?->diffForHumans() ?? '—' }}
                                                    </span>
                                                </div>
                                            </button>

                                            <div x-show="open" x-cloak class="px-4 pb-4 space-y-5 bg-gray-50/60 dark:bg-gray-900/20">
                                                @if ($session['modules']->isNotEmpty())
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2 pt-1">{{ __('L1 — Module & Trainer Ratings') }}</p>
                                                        <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                                                            <table class="min-w-full text-sm">
                                                                <thead>
                                                                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                                                        <th class="py-2 pl-4 pr-4">{{ __('Module') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Module Rating') }}</th>
                                                                        <th class="py-2 pr-4">{{ __('Trainer Rating') }}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                                    @foreach ($session['modules'] as $module)
                                                                        <tr>
                                                                            <td class="py-2 pl-4 pr-4 text-gray-700 dark:text-gray-200">{{ $module['module'] }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $module['module_rating'] ?? '—' }}</td>
                                                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $module['trainer_rating'] ?? '—' }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                            {{ __('Overall Trainer Rating:') }} <span class="font-semibold text-[#152A4E] dark:text-white">{{ $session['overall_trainer_rating'] ?? '—' }}</span>
                                                            {{ __('(reflected on the Instructors tab when exactly one instructor teaches this training)') }}
                                                        </p>
                                                    </div>
                                                @endif

                                                @if ($session['pretest_score'] !== null || $session['posttest_score'] !== null)
                                                    @php $change = ($session['pretest_score'] !== null && $session['posttest_score'] !== null) ? $session['posttest_score'] - $session['pretest_score'] : null; @endphp
                                                    <div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{{ __('L2 — Pre/Post Test') }}</p>
                                                        <div class="flex flex-wrap gap-3">
                                                            <div class="flex-1 min-w-[7rem] rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                                                <p class="text-xs text-gray-400">{{ __('Pre-Test') }}</p>
                                                                <p class="text-xl font-bold text-[#152A4E] dark:text-white">{{ $session['pretest_score'] ?? '—' }}</p>
                                                            </div>
                                                            <div class="flex-1 min-w-[7rem] rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                                                <p class="text-xs text-gray-400">{{ __('Post-Test') }}</p>
                                                                <p class="text-xl font-bold text-[#152A4E] dark:text-white">{{ $session['posttest_score'] ?? '—' }}</p>
                                                            </div>
                                                            <div class="flex-1 min-w-[7rem] rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                                                <p class="text-xs text-gray-400">{{ __('Change') }}</p>
                                                                <p class="text-xl font-bold {{ $change === null ? 'text-gray-400' : ($change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">
                                                                    {{ $change === null ? '—' : ($change >= 0 ? '+'.$change : $change) }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Graduates by Region (map) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Region') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Regions with completed trainings glow in proportion to their graduate count. This traces the OCD regional office boundaries, not exact facility locations — hover an area to see its numbers.') }}
                </p>

                <div class="graduates-map-panel rounded-lg overflow-hidden">
                    <div id="graduatesRegionMap"></div>
                </div>

                <div class="flex items-center gap-2 mt-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('Fewer graduates') }}</span>
                    <span class="flex h-2.5 w-24 rounded-full overflow-hidden">
                        <span class="flex-1" style="background:#1e355c"></span>
                        <span class="flex-1" style="background:#28518f"></span>
                        <span class="flex-1" style="background:#2f6fc4"></span>
                        <span class="flex-1" style="background:#3B82F6"></span>
                    </span>
                    <span>{{ __('More graduates') }}</span>
                    <span class="inline-flex items-center gap-1.5 ml-3">
                        <span class="w-2.5 h-2.5 rounded-full inline-block border border-gray-400" style="background:transparent"></span>
                        {{ __('No completed trainings on file') }}
                    </span>
                </div>
            </div>

            <style>
                .graduates-map-panel {
                    background: radial-gradient(ellipse at 50% 40%, #f6f8fc 0%, #e7ecf5 70%);
                }
                #graduatesRegionMap {
                    height: 460px;
                    background: transparent;
                }
                .region-tooltip {
                    background: rgba(255, 255, 255, 0.97) !important;
                    border: 1px solid rgba(59, 130, 246, 0.35) !important;
                    border-radius: 10px !important;
                    box-shadow: 0 8px 24px rgba(21, 42, 78, 0.18) !important;
                    color: #152A4E !important;
                    padding: 10px 12px !important;
                }
                .region-tooltip::before {
                    display: none !important;
                }
                .leaflet-container {
                    background: transparent !important;
                    outline: none;
                }
                .region-glow-dot {
                    filter: drop-shadow(0 0 6px rgba(59, 130, 246, 0.9));
                }
                .region-hover-glow {
                    filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.85));
                }
            </style>

            <!-- Graduates by LGU -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by LGU') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    {{ __('Completed trainings grouped by the LGU recorded on Summary — the region-level breakdown is mapped above.') }}
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-400 mb-5">
                    {{ __('"Teams Organized" isn\'t shown here since there\'s no team data in the system yet.') }}
                </p>

                @if (empty($graduatesByLgu))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed trainings with an LGU recorded yet.') }}
                    </div>
                @else
                    @php $maxTotal = max(array_column($graduatesByLgu, 'total')); @endphp
                    <div class="space-y-3">
                        @foreach ($graduatesByLgu as $row)
                            <div class="flex items-center gap-3">
                                <div class="w-48 shrink-0 text-xs font-medium text-gray-600 dark:text-gray-300 truncate">{{ $row['lgu'] }}</div>
                                <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full bg-[#152A4E] dark:bg-[#E2762D] transition-all" style="width: {{ round(($row['total'] / $maxTotal) * 100) }}%;"></div>
                                </div>
                                <div class="w-8 text-right text-xs font-semibold text-gray-700 dark:text-gray-200 tabular-nums">{{ $row['total'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script>
        (function () {
            const regionData = @json($graduatesByRegion);

            // The public boundary dataset labels regions by their old/full
            // names — map those to the region keys used throughout this app.
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

            const ACCENT = '#3B82F6';
            const BORDER_IDLE = 'rgba(21, 42, 78, 0.22)';
            const opacityScale = [0.22, 0.4, 0.55, 0.75];

            const maxGraduates = Math.max(1, ...Object.values(regionData).map((r) => r.graduates));

            const opacityFor = (graduates) => {
                if (!graduates) return 0;
                const step = Math.min(opacityScale.length - 1, Math.floor((graduates / maxGraduates) * opacityScale.length));
                return opacityScale[step];
            };

            // Scroll-to-zoom stays off so scrolling the page past the map
            // doesn't get captured by it — everything else (drag, +/- controls,
            // double-click, pinch) is enabled.
            const map = L.map('graduatesRegionMap', {
                attributionControl: false,
                scrollWheelZoom: false,
            }).setView([12.8797, 121.7740], 5);

            fetch('https://cdn.jsdelivr.net/gh/macoymejia/geojsonph@master/Regions/Regions.bit.json')
                .then((response) => response.json())
                .then((geojson) => {
                    const layer = L.geoJSON(geojson, {
                        style: (feature) => {
                            const key = regionNameMap[feature.properties.REGION];
                            const data = regionData[key];
                            const active = !!(data && data.graduates);

                            return {
                                fillColor: ACCENT,
                                fillOpacity: opacityFor(data ? data.graduates : 0),
                                color: active ? ACCENT : BORDER_IDLE,
                                weight: active ? 1.5 : 1,
                            };
                        },
                        onEachFeature: (feature, featureLayer) => {
                            const key = regionNameMap[feature.properties.REGION] ?? feature.properties.REGION;
                            const data = regionData[key] || { graduates: 0, trainings: 0 };
                            const baseOpacity = opacityFor(data.graduates);
                            const baseWeight = data.graduates ? 1.5 : 1;
                            const baseColor = data.graduates ? ACCENT : BORDER_IDLE;

                            featureLayer.bindTooltip(
                                '<div style="font-size:12px;min-width:150px">' +
                                '<div style="font-weight:700;color:#152A4E;letter-spacing:.02em;margin-bottom:3px">' + key + '</div>' +
                                '<div style="color:#6b7280">Graduates: <strong style="color:' + ACCENT + '">' + data.graduates + '</strong></div>' +
                                '<div style="color:#6b7280">Trainings: <strong style="color:' + ACCENT + '">' + data.trainings + '</strong></div>' +
                                '</div>',
                                { sticky: true, className: 'region-tooltip', direction: 'top' }
                            );

                            featureLayer.on('mouseover', function () {
                                this.setStyle({ fillOpacity: Math.min(0.9, baseOpacity + 0.3), weight: 2.5, color: ACCENT });
                                this.bringToFront();
                                const el = this.getElement();
                                if (el) el.classList.add('region-hover-glow');
                            });
                            featureLayer.on('mouseout', function () {
                                this.setStyle({ fillOpacity: baseOpacity, weight: baseWeight, color: baseColor });
                                const el = this.getElement();
                                if (el) el.classList.remove('region-hover-glow');
                            });

                            // A soft glowing point at each active region's centroid,
                            // echoing a pin without needing exact facility coordinates.
                            if (data.graduates) {
                                const center = featureLayer.getBounds().getCenter();
                                L.circleMarker(center, {
                                    radius: 4,
                                    color: '#fff',
                                    weight: 1,
                                    fillColor: ACCENT,
                                    fillOpacity: 1,
                                    className: 'region-glow-dot',
                                    interactive: false,
                                }).addTo(map);
                            }
                        },
                    }).addTo(map);

                    map.fitBounds(layer.getBounds(), { padding: [16, 16] });
                    setTimeout(() => map.invalidateSize(), 200);
                });
        })();
    </script>
</x-app-layout>
