<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tools') }}
        </h2>
    </x-slot>

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
                        const link = event.target.closest('a');
                        if (!link || !container.contains(link) || !link.href) {
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

            <!-- Not yet available -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($notYetAvailable as $tool)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-7">
                        <div class="flex items-start justify-between mb-4">
                            <span class="inline-flex items-center text-[11px] font-semibold rounded-full border px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700">
                                {{ __('Not Yet Available') }}
                            </span>
                        </div>
                        <h3 class="font-bold text-[#152A4E] dark:text-white mb-1">{{ __($tool['title']) }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __($tool['description']) }}</p>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
