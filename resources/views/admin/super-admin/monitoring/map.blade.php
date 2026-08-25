<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Map of Training Graduates and Teams') }}
        </h2>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
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
                        <x-input-label for="regions" :value="__('Regions')" />
                        <select id="regions" name="regions[]" multiple size="1"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            @foreach ($regions as $region)
                                <option value="{{ $region }}" @selected(in_array($region, $filters['regions']))>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="category" :value="__('APB / TA')" />
                        <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">{{ __('APB and TA') }}</option>
                            @foreach ($categoryLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-4 py-2 hover:bg-[#1E3A66] transition">
                            {{ __('Apply Filters') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                @foreach ([
                    ['label' => 'Training Graduates', 'value' => $summary['graduates']],
                    ['label' => 'Teams Organized', 'value' => $summary['teams']],
                    ['label' => 'Trainings Conducted', 'value' => $summary['trainings']],
                    ['label' => 'LGUs Covered', 'value' => $summary['lgus']],
                    ['label' => 'NGAs Covered', 'value' => $summary['ngas']],
                    ['label' => 'Regions Covered', 'value' => count($regionBreakdown).' / '.count($regions)],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __($card['label']) }}</div>
                        <div class="text-2xl font-bold text-[#152A4E] dark:text-white mt-1">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#03055A"></span> {{ __('LGU') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#E2762D"></span> {{ __('NGA') }}</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#0EA5E9"></span> {{ __('Region-level (no LGU/NGA specified)') }}</span>
            </div>

            <!-- Map -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-3">
                <div id="graduatesMap" style="height: 480px; border-radius: 0.5rem;"></div>
            </div>

            <!-- Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Training Summary per LGU / NGA') }}</h2>
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
                                    <tr>
                                        <td class="py-3 pr-4">
                                            <div class="font-medium text-[#152A4E] dark:text-white">{{ $point['name'] }}</div>
                                            @if ($point['agency_type'])
                                                <div class="text-xs text-gray-400">{{ $point['agency_type'] }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $point['region'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $point['trainings'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $point['graduates'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $point['teams'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-5">{{ __('Graduates and Teams per Region') }}</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4">{{ __('Region') }}</th>
                                    <th class="py-2 pr-4">{{ __('OCD Regional Office') }}</th>
                                    <th class="py-2 pr-4">{{ __('Trainings') }}</th>
                                    <th class="py-2 pr-4">{{ __('Graduates') }}</th>
                                    <th class="py-2 pr-4">{{ __('Teams') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($regionBreakdown as $row)
                                    <tr>
                                        <td class="py-3 pr-4 font-medium text-[#152A4E] dark:text-white">{{ $row['region'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ 'OCDRO '.$row['region'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['trainings'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['graduates'] }}</td>
                                        <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $row['teams'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        (function () {
            const points = @json($mapPoints);
            const map = L.map('graduatesMap', { scrollWheelZoom: false }).setView([12.8797, 121.7740], 5);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const colorFor = (agencyType) => agencyType === 'LGU' ? '#03055A' : (agencyType === 'NGA' ? '#E2762D' : '#0EA5E9');

            const bounds = [];

            points.forEach((point) => {
                const radius = Math.max(6, Math.min(30, 5 + Math.sqrt(point.graduates)));
                const marker = L.circleMarker([point.latitude, point.longitude], {
                    radius,
                    color: colorFor(point.agency_type),
                    fillColor: colorFor(point.agency_type),
                    fillOpacity: 0.45,
                    weight: 1.5,
                });

                const subtitle = [point.agency_type, point.region].filter(Boolean).join(' · ');
                marker.bindPopup(
                    '<div style="font-size:12px;min-width:160px">' +
                    '<div style="font-weight:600;color:#152A4E">' + point.name + '</div>' +
                    '<div style="color:#6b7280;margin-bottom:4px">' + subtitle + '</div>' +
                    '<div>Graduates: ' + point.graduates + '</div>' +
                    '<div>Teams organized: ' + point.teams + '</div>' +
                    '<div>Trainings: ' + point.trainings + ' (APB ' + point.apb + ' / TA ' + point.ta + ')</div>' +
                    '</div>'
                );

                marker.addTo(map);
                bounds.push([point.latitude, point.longitude]);
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 9 });
            }

            setTimeout(() => map.invalidateSize(), 200);
        })();
    </script>
</x-app-layout>
