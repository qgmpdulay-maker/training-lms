<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Map of Training Graduates and Teams') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Filter') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Narrow the map and tables below by date range, region, or training.') }}</p>
                </div>

                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <x-input-label for="from" :value="__('From')" />
                        <x-text-input id="from" name="from" type="date" class="mt-1 block w-full text-sm" value="{{ $filters['from'] }}" />
                    </div>
                    <div>
                        <x-input-label for="until" :value="__('Until')" />
                        <x-text-input id="until" name="until" type="date" class="mt-1 block w-full text-sm" value="{{ $filters['until'] }}" />
                    </div>
                    <div>
                        <x-input-label :value="__('Regions')" />
                        @if ($regionLocked)
                            <div class="mt-1 flex items-center h-[38px] px-3 rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                                {{ $filters['regions'][0] ?? '—' }}
                            </div>
                        @else
                            <div x-data="{ open: false }" class="relative mt-1">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-2 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-2 px-3 text-left hover:border-[#152A4E] dark:hover:border-white/40 transition">
                                    <span class="truncate">
                                        @if (empty($filters['regions']))
                                            {{ __('All Regions') }}
                                        @elseif (count($filters['regions']) === 1)
                                            {{ $filters['regions'][0] }}
                                        @else
                                            {{ __(':count regions selected', ['count' => count($filters['regions'])]) }}
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
                                            <input type="checkbox" name="regions[]" value="{{ $regionOption }}" @checked(in_array($regionOption, $filters['regions']))
                                                class="rounded border-gray-300 dark:border-gray-600 text-[#152A4E] focus:ring-[#152A4E]">
                                            {{ $regionOption }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="training_title" :value="__('Training')" />
                        <select id="training_title" name="training_title" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">{{ __('All Trainings') }}</option>
                            @foreach ($trainingTitles as $title)
                                <option value="{{ $title }}" @selected($filters['training_title'] === $title)>{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-4 py-2 hover:bg-[#1E3A66] transition">
                            {{ __('Apply Filters') }}
                        </button>
                        @if (! empty($filters['regions']) || $filters['training_title'])
                            <a href="{{ route('admin.monitoring.map', array_filter(['from' => $filters['from'], 'until' => $filters['until']])) }}"
                                class="shrink-0 text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-[#152A4E] dark:hover:text-white transition whitespace-nowrap">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach ([
                    ['label' => 'Training Graduates', 'value' => $summary['graduates'], 'accent' => '#152A4E'],
                    ['label' => 'Teams Organized', 'value' => $summary['teams'], 'accent' => '#E2762D'],
                    ['label' => 'Trainings Conducted', 'value' => $summary['trainings'], 'accent' => '#2a78d6'],
                    ['label' => 'LGUs Covered', 'value' => $summary['lgus'], 'accent' => '#03055A'],
                    ['label' => 'NGAs Covered', 'value' => $summary['ngas'], 'accent' => '#E2762D'],
                    ['label' => 'Regions Covered', 'value' => collect($mapPoints)->pluck('region')->filter()->unique()->count().' / '.count($regions), 'accent' => '#0ca30c'],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 border-l-4" style="border-left-color: {{ $card['accent'] }};">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 min-h-[2rem]">{{ __($card['label']) }}</div>
                        <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Map -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between flex-wrap gap-3 px-6 sm:px-8 pt-6 sm:pt-8 pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Graduates by Location') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Marker size scales with graduate count — hover a marker for its details, or hover anywhere in a region to see that region\'s totals.') }}</p>
                    </div>
                    <div class="flex items-center gap-4 flex-wrap text-xs font-medium text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#03055A"></span>{{ __('LGU') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#E2762D"></span>{{ __('NGA') }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#0EA5E9"></span>{{ __('Region-level') }}</span>
                    </div>
                </div>

                @if (empty($mapPoints))
                    <div class="mx-6 sm:mx-8 mb-6 sm:mb-8 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed trainings match these filters yet.') }}
                    </div>
                @endif

                @include('admin.partials.graduates-map', [
                    'mapId' => 'graduatesMap',
                    'points' => $mapPoints,
                    'regionDeployed' => $regionDeployed,
                    'onlyRegion' => $regionLocked ? ($filters['regions'][0] ?? null) : null,
                ])
            </div>

            <!-- Tables -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Training Summary per LGU / NGA') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    {{ __('Top 15 locations by graduate count.') }}
                    @if (count($mapPoints) > 15)
                        {{ __('Showing 15 of :count.', ['count' => count($mapPoints)]) }}
                    @endif
                </p>

                @if (empty($mapPoints))
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No completed trainings match these filters yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
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
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $point['region'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['trainings'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['graduates'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300 tabular-nums">{{ $point['teams'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
