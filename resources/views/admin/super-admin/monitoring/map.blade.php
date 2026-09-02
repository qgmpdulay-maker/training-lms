<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Map of Training Graduates and Teams') }}
        </h2>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-8">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-[#152A4E] dark:text-white mb-1">{{ __('Filter') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Narrow the map and tables below by date range, region, or category.') }}</p>
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
                        <x-input-label for="category" :value="__('APB / TA')" />
                        <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">{{ __('APB and TA') }}</option>
                            @foreach ($categoryLabels as $value => $label)
                                <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="w-full inline-flex items-center justify-center bg-[#152A4E] text-white text-sm font-semibold rounded-lg px-4 py-2 hover:bg-[#1E3A66] transition">
                            {{ __('Apply Filters') }}
                        </button>
                        @if (! empty($filters['regions']) || $filters['category'])
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

                <div class="graduates-map-panel px-3 pb-3">
                    <div id="graduatesMap" style="height: 520px; border-radius: 0.5rem;"></div>
                </div>
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

    <style>
        .graduates-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #f6f8fc 0%, #e7ecf5 70%);
            /* Leaflet's own panes/controls use z-index up to 1000, which otherwise
               escapes this panel and renders on top of the app header/sidebar
               (both z-40/z-50) since this panel doesn't form its own stacking
               context by default. Isolating it keeps Leaflet's stack contained. */
            position: relative;
            isolation: isolate;
        }
        .dark .graduates-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #1c2740 0%, #131b2e 70%);
        }
        #graduatesMap {
            background: transparent;
        }
        .leaflet-container {
            background: transparent !important;
            outline: none;
            font-family: inherit;
        }
        .graduates-popup .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(21, 42, 78, 0.18);
            padding: 2px;
        }
        .graduates-popup .leaflet-popup-content {
            margin: 10px 12px;
            font-size: 12px;
            min-width: 160px;
        }
        .graduates-popup .leaflet-popup-tip {
            box-shadow: none;
        }
        .graduates-glow-dot {
            filter: drop-shadow(0 0 4px rgba(59, 130, 246, 0.6));
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
        .region-hover-glow {
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.85));
        }
        .graduates-cluster-icon {
            background: transparent !important;
            border: none !important;
        }
        .graduates-cluster-badge {
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
        (function () {
            const points = @json($mapPoints);
            const map = L.map('graduatesMap', { attributionControl: false, scrollWheelZoom: false }).setView([12.8797, 121.7740], 5);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
            }).addTo(map);

            L.control.attribution({ position: 'bottomright', prefix: false }).addAttribution('&copy; OpenStreetMap contributors').addTo(map);

            const colorFor = (agencyType) => agencyType === 'LGU' ? '#03055A' : (agencyType === 'NGA' ? '#E2762D' : '#0EA5E9');

            const bounds = [];

            // Nearby LGU/NGA points are grouped into a single cluster badge until
            // the map is zoomed in enough to tell them apart — with hundreds of
            // points, showing every marker (and its popup) at once made dense
            // areas like Metro Manila unreadable.
            const clusterGroup = L.markerClusterGroup({
                maxClusterRadius: 45,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                iconCreateFunction: (cluster) => {
                    const count = cluster.getChildCount();
                    const size = count < 10 ? 32 : (count < 50 ? 40 : 48);
                    return L.divIcon({
                        html: '<div class="graduates-cluster-badge" style="width:' + size + 'px;height:' + size + 'px;line-height:' + size + 'px;">' + count + '</div>',
                        className: 'graduates-cluster-icon',
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
                    className: 'graduates-glow-dot',
                    // Region polygons live in the default overlayPane and jump to
                    // its front on hover (see bringToFront() below) — pinning
                    // markers to markerPane keeps them above that shuffle so a
                    // region hover can never steal the marker's own hover.
                    pane: 'markerPane',
                });

                const subtitle = [point.agency_type, point.region].filter(Boolean).join(' · ');
                marker.bindPopup(
                    '<div style="font-weight:700;color:#152A4E;letter-spacing:.01em;margin-bottom:2px">' + point.name + '</div>' +
                    '<div style="color:#6b7280;margin-bottom:6px">' + subtitle + '</div>' +
                    '<div style="color:#374151">' + '{{ __('Graduates') }}: <strong>' + point.graduates + '</strong></div>' +
                    '<div style="color:#374151">' + '{{ __('Teams organized') }}: <strong>' + point.teams + '</strong></div>' +
                    '<div style="color:#374151">' + '{{ __('Trainings') }}: <strong>' + point.trainings + '</strong> (APB ' + point.apb + ' / TA ' + point.ta + ')</div>',
                    { className: 'graduates-popup', closeButton: false }
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
            // markers — shaded by total graduates in that region (summed from the
            // same points above), so hovering anywhere in a region (not just on a
            // marker) surfaces that region's totals.
            //
            // The public boundary dataset labels regions by their old/full names —
            // map those to the region keys used throughout this app.
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
                                { sticky: true, className: 'region-tooltip', direction: 'top' }
                            );

                            featureLayer.on('mouseover', function () {
                                this.setStyle({ fillOpacity: Math.min(0.9, baseOpacity + 0.3), weight: 2.5, color: REGION_ACCENT });
                                this.bringToFront();
                                const el = this.getElement();
                                if (el) el.classList.add('region-hover-glow');
                            });
                            featureLayer.on('mouseout', function () {
                                this.setStyle({ fillOpacity: baseOpacity, weight: baseWeight, color: baseColor });
                                const el = this.getElement();
                                if (el) el.classList.remove('region-hover-glow');
                            });
                        },
                    }).addTo(map);

                    // Markers were added first but Leaflet stacks later-added layers on
                    // top by default — push the region shapes back underneath them so
                    // markers stay directly clickable/hoverable.
                    layer.bringToBack();
                })
                .catch(() => {});

            setTimeout(() => map.invalidateSize(), 200);
        })();
    </script>
</x-app-layout>
