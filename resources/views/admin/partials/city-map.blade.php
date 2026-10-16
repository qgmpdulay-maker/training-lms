{{--
    Graduates by Location, by city: each city or municipality shaded by how
    many graduates live there, and hovering one shows its graduates, deployed
    people, and trainings. No markers. Used by both admin dashboards.

    Expects $mapId, $cityTotals (MonitoringController::cityTotals()), and
    optionally $onlyRegion (a Regional Admin's region, drawn with a heavier
    outline) and $height.

    The outlines come from public/geo (see scripts/build-ph-city-boundaries.py)
    and are matched to the totals by city id. Each map's data rides on its own
    element, so renderCityMaps() can redraw every map on the page after the
    Super Admin dashboard swaps in a re-filtered copy of this partial.
--}}
@php
    $height ??= 480;
    $onlyRegion ??= null;
@endphp

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .city-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #f6f8fc 0%, #e7ecf5 70%);
            /* Keeps Leaflet's own z-indexed panes from escaping over the app
               header and sidebar. */
            position: relative;
            isolation: isolate;
        }
        .dark .city-map-panel {
            background: radial-gradient(ellipse at 50% 40%, #1c2740 0%, #131b2e 70%);
        }
        .city-map-panel .leaflet-container {
            background: transparent;
            font-family: inherit;
        }
        .city-map-tooltip {
            background: rgba(255, 255, 255, 0.97) !important;
            border: 1px solid rgba(21, 42, 78, 0.15) !important;
            border-radius: 10px !important;
            box-shadow: 0 8px 24px rgba(21, 42, 78, 0.18) !important;
            padding: 10px 12px !important;
        }
        .city-map-tooltip::before {
            display: none !important;
        }
        .city-map-tip {
            min-width: 170px;
            font-size: 12px;
            color: #374151;
        }
        .city-map-tip-title {
            font-weight: 700;
            color: #152A4E;
        }
        .city-map-tip-sub {
            color: #6b7280;
            margin-bottom: 6px;
        }
        .city-map-tip-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
        }
        .city-map-tip-row strong {
            color: #152A4E;
            font-variant-numeric: tabular-nums;
        }
    </style>

    <script>
        (function () {
            // Sequential blue, light to dark: few graduates to many.
            const STEPS = ['#b7d3f6', '#6da7ec', '#2a78d6', '#1c5cab', '#0d366b'];
            const LABELS = {
                graduates: @json(__('Graduates')),
                deployed: @json(__('Deployed')),
                trainings: @json(__('Trainings')),
                legend: @json(__('Graduates per city')),
            };
            let outlines = null;
            let maps = [];

            function loadOutlines() {
                outlines ??= Promise.all([
                    fetch(@json(asset('geo/ph-cities.json'))).then((response) => response.json()),
                    fetch(@json(asset('geo/ph-regions.json'))).then((response) => response.json()),
                ]);

                return outlines;
            }

            // Upper bounds of up to five classes holding roughly equal numbers
            // of cities, so a few very large cities don't wash out the rest.
            function classBounds(values) {
                const sorted = [...values].sort((a, b) => a - b);
                const bounds = [];
                for (let k = 1; k <= STEPS.length; k++) {
                    const value = sorted[Math.ceil(k * sorted.length / STEPS.length) - 1];
                    if (value !== undefined && !bounds.includes(value)) {
                        bounds.push(value);
                    }
                }

                return bounds;
            }

            // Fewer than five classes still span light to dark.
            function stepFor(index, count) {
                return count === 1 ? STEPS[2] : STEPS[Math.round(index * (STEPS.length - 1) / (count - 1))];
            }

            function tooltipFor(city) {
                const box = document.createElement('div');
                box.className = 'city-map-tip';

                const title = document.createElement('div');
                title.className = 'city-map-tip-title';
                title.textContent = city.name;
                const province = document.createElement('div');
                province.className = 'city-map-tip-sub';
                province.textContent = city.province;
                box.append(title, province);

                ['graduates', 'deployed', 'trainings'].forEach((key) => {
                    const row = document.createElement('div');
                    row.className = 'city-map-tip-row';
                    const label = document.createElement('span');
                    label.textContent = LABELS[key];
                    const value = document.createElement('strong');
                    value.textContent = city[key].toLocaleString();
                    row.append(label, value);
                    box.append(row);
                });

                return box;
            }

            function drawLegend(el, bounds) {
                const box = document.querySelector('[data-city-map-legend="' + el.id + '"]');
                if (!box) {
                    return;
                }
                box.replaceChildren();
                if (!bounds.length) {
                    return;
                }

                const title = document.createElement('span');
                title.className = 'font-semibold text-gray-600 dark:text-gray-300 mr-1';
                title.textContent = LABELS.legend;
                box.append(title);

                let low = 1;
                bounds.forEach((high, index) => {
                    const item = document.createElement('span');
                    item.className = 'inline-flex items-center gap-1.5 tabular-nums';
                    const swatch = document.createElement('span');
                    swatch.className = 'inline-block w-3 h-3 rounded-sm';
                    swatch.style.background = stepFor(index, bounds.length);
                    item.append(swatch, low === high ? String(low) : low.toLocaleString() + '–' + high.toLocaleString());
                    box.append(item);
                    low = high + 1;
                });
            }

            function render(el) {
                const cities = JSON.parse(el.dataset.cityMap || '[]');
                const onlyRegion = el.dataset.onlyRegion || null;
                const byId = new Map(cities.map((city) => [city.id, city]));
                const bounds = classBounds(cities.map((city) => city.graduates));
                const colourFor = (graduates) => {
                    const index = bounds.findIndex((bound) => graduates <= bound);
                    return stepFor(index === -1 ? bounds.length - 1 : index, bounds.length);
                };

                drawLegend(el, bounds);

                // zoomSnap lets fitBounds() fill the frame instead of stopping a whole
                // zoom level short — the country is long and narrow.
                const map = L.map(el, { attributionControl: false, scrollWheelZoom: false, zoomSnap: 0.25 }).setView([12.8797, 121.774], 5);
                maps.push(map);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
                L.control.attribution({ position: 'bottomright', prefix: false })
                    .addAttribution('&copy; OpenStreetMap contributors · Boundaries: <a href="https://www.geoboundaries.org" target="_blank" rel="noopener">geoBoundaries</a> (NAMRIA, PSA, OCHA), CC BY 3.0 IGO')
                    .addTo(map);

                loadOutlines().then(([cityOutlines, regionOutlines]) => {
                    // Redrawn (or removed) while the outlines were loading.
                    if (!maps.includes(map)) {
                        return;
                    }

                    const regionLayer = L.geoJSON(regionOutlines, {
                        interactive: false,
                        style: (feature) => {
                            const own = feature.properties.region === onlyRegion;
                            return {
                                fill: false,
                                color: '#152A4E',
                                weight: own ? 2 : 1,
                                opacity: onlyRegion && !own ? 0.15 : 0.4,
                            };
                        },
                    }).addTo(map);

                    const cityLayer = L.geoJSON(cityOutlines, {
                        filter: (feature) => byId.has(feature.properties.id),
                        // Outlined in their own colour: zoomed out to the whole
                        // country a city is only a few pixels wide, and a
                        // contrasting border would swallow its fill.
                        style: (feature) => {
                            const colour = colourFor(byId.get(feature.properties.id).graduates);
                            return { fillColor: colour, fillOpacity: 0.85, color: colour, weight: 1, opacity: 1 };
                        },
                        onEachFeature: (feature, layer) => {
                            layer.bindTooltip(tooltipFor(byId.get(feature.properties.id)), {
                                sticky: true,
                                // Beside the cursor, flipping sides near the
                                // edge — above it got clipped near the top.
                                direction: 'auto',
                                offset: [12, 0],
                                className: 'city-map-tooltip',
                            });
                            layer.on('mouseover', () => {
                                layer.setStyle({ color: '#152A4E', weight: 2.5 });
                                layer.bringToFront();
                            });
                            layer.on('mouseout', () => cityLayer.resetStyle(layer));
                        },
                    }).addTo(map);

                    if (cityLayer.getLayers().length) {
                        map.fitBounds(cityLayer.getBounds(), { padding: [24, 24], maxZoom: 10 });
                    } else if (onlyRegion) {
                        const own = regionLayer.getLayers().find((layer) => layer.feature.properties.region === onlyRegion);
                        if (own) {
                            map.fitBounds(own.getBounds(), { padding: [24, 24] });
                        }
                    }
                }).catch(() => {});

                setTimeout(() => map.invalidateSize(), 200);
            }

            window.renderCityMaps = function () {
                maps.forEach((map) => map.remove());
                maps = [];
                document.querySelectorAll('[data-city-map]').forEach(render);
            };

            document.addEventListener('DOMContentLoaded', window.renderCityMaps);
        })();
    </script>
@endonce

<div class="city-map-panel px-3 pb-3">
    <div id="{{ $mapId }}" data-city-map="{{ json_encode($cityTotals['cities']) }}" data-only-region="{{ $onlyRegion }}"
        style="height: {{ $height }}px; border-radius: 0.5rem;"></div>
</div>
<div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-2 px-6 pb-5 pt-1">
    <div data-city-map-legend="{{ $mapId }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400"></div>
    @if ($cityTotals['unplaced'] > 0)
        <p class="text-xs text-amber-700 dark:text-amber-400 max-w-md">
            {{ trans_choice(':count graduate isn\'t on the map|:count graduates aren\'t on the map', $cityTotals['unplaced'], ['count' => number_format($cityTotals['unplaced'])]) }}
            {{ __('— no City on file, or one that isn\'t a city or municipality we recognise.') }}
            <a href="{{ route('admin.deployments.index', ['status' => 'no_city']) }}" class="font-semibold underline hover:no-underline">{{ __('Set their city') }}</a>
        </p>
    @endif
</div>
