{{--
    Graduates by Location: each city or municipality — or, switched to the
    Region view, each region — shaded by how many graduates live there, and
    hovering one shows its graduates, deployed people, and trainings. No
    markers. Used by both admin dashboards.

    Expects $mapId, $cityTotals (MonitoringController::cityTotals()), and
    optionally $onlyRegion (a Regional Admin's region, drawn with a heavier
    outline) and $height.

    The outlines come from public/geo (see scripts/build-ph-city-boundaries.py)
    and are matched to the totals by city id or region name. Each map's data
    rides on its own element, so renderCityMaps() can redraw every map on the
    page after the dashboard swaps in a re-filtered copy of this partial; the
    Region / City choice carries over.
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
        /* Region / City switch, over the map's top-right corner. */
        .city-map-mode {
            position: absolute;
            top: 12px;
            right: 24px;
            z-index: 1001;
            display: inline-flex;
            gap: 2px;
            padding: 3px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(21, 42, 78, 0.15);
            border-radius: 9999px;
            box-shadow: 0 2px 8px rgba(21, 42, 78, 0.12);
        }
        .city-map-mode button {
            padding: 4px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            transition: background-color 0.15s, color 0.15s;
        }
        .city-map-mode button:hover {
            color: #152A4E;
        }
        .city-map-mode button[aria-pressed="true"] {
            background: #152A4E;
            color: #fff;
        }
        .city-map-mode button:focus-visible {
            outline: 2px solid #2a78d6;
            outline-offset: 1px;
        }
        .dark .city-map-mode {
            background: rgba(31, 41, 55, 0.95);
            border-color: rgba(255, 255, 255, 0.12);
        }
        .dark .city-map-mode button {
            color: #d1d5db;
        }
        .dark .city-map-mode button:hover {
            color: #fff;
        }
        .dark .city-map-mode button[aria-pressed="true"] {
            background: #E2762D;
            color: #fff;
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
                legend: {
                    city: @json(__('Graduates per city')),
                    region: @json(__('Graduates per region')),
                },
                cities: {
                    one: @json(__(':count city or municipality with graduates')),
                    many: @json(__(':count cities and municipalities with graduates')),
                },
            };
            const MODE_KEY = 'graduatesMap.mode';
            let outlines = null;
            let maps = [];
            // City unless the viewer last picked Region — kept across the
            // dashboard's re-filtered redraws and, where the browser allows
            // it, across visits.
            let preferredMode = 'city';
            try {
                if (localStorage.getItem(MODE_KEY) === 'region') {
                    preferredMode = 'region';
                }
            } catch (e) {}

            function loadOutlines() {
                outlines ??= Promise.all([
                    fetch(@json(asset('geo/ph-cities.json'))).then((response) => response.json()),
                    fetch(@json(asset('geo/ph-regions.json'))).then((response) => response.json()),
                ]);

                return outlines;
            }

            // Upper bounds of up to five classes holding roughly equal numbers
            // of places, so a few very large ones don't wash out the rest.
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

            function cityCount(count) {
                return (count === 1 ? LABELS.cities.one : LABELS.cities.many).replace(':count', count.toLocaleString());
            }

            function tooltipFor(place, subtitle) {
                const box = document.createElement('div');
                box.className = 'city-map-tip';

                const title = document.createElement('div');
                title.className = 'city-map-tip-title';
                title.textContent = place.name;
                const sub = document.createElement('div');
                sub.className = 'city-map-tip-sub';
                sub.textContent = subtitle;
                box.append(title, sub);

                ['graduates', 'deployed', 'trainings'].forEach((key) => {
                    const row = document.createElement('div');
                    row.className = 'city-map-tip-row';
                    const label = document.createElement('span');
                    label.textContent = LABELS[key];
                    const value = document.createElement('strong');
                    value.textContent = place[key].toLocaleString();
                    row.append(label, value);
                    box.append(row);
                });

                return box;
            }

            function drawLegend(el, bounds, heading) {
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
                title.textContent = heading;
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
                const onlyRegion = el.dataset.onlyRegion || null;
                // Each view's places, keyed the way its outlines are.
                const views = {
                    city: {
                        places: new Map(JSON.parse(el.dataset.cityMap || '[]').map((city) => [city.id, city])),
                        keyOf: (feature) => feature.properties.id,
                        subtitle: (city) => city.province,
                    },
                    region: {
                        places: new Map(JSON.parse(el.dataset.regionMap || '[]').map((region) => [region.name, region])),
                        keyOf: (feature) => feature.properties.region,
                        subtitle: (region) => cityCount(region.cities),
                    },
                };
                const switcher = document.querySelector('[data-city-map-mode="' + el.id + '"]');
                let mode = preferredMode;
                let show = () => {};

                const markSwitcher = () => switcher?.querySelectorAll('button').forEach((button) => {
                    button.setAttribute('aria-pressed', String(button.dataset.mode === mode));
                });
                markSwitcher();
                switcher?.querySelectorAll('button').forEach((button) => {
                    button.onclick = () => {
                        if (button.dataset.mode === mode) {
                            return;
                        }
                        mode = preferredMode = button.dataset.mode;
                        try {
                            localStorage.setItem(MODE_KEY, mode);
                        } catch (e) {}
                        markSwitcher();
                        show();
                    };
                });

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

                    // The current view's shading, swapped out when the viewer
                    // switches between Region and City.
                    let shading = null;
                    show = () => {
                        const view = views[mode];
                        const bounds = classBounds([...view.places.values()].map((place) => place.graduates));
                        const colourFor = (graduates) => {
                            const index = bounds.findIndex((bound) => graduates <= bound);
                            return stepFor(index === -1 ? bounds.length - 1 : index, bounds.length);
                        };

                        drawLegend(el, bounds, LABELS.legend[mode]);
                        shading?.remove();

                        const layer = L.geoJSON(mode === 'region' ? regionOutlines : cityOutlines, {
                            filter: (feature) => view.places.has(view.keyOf(feature)),
                            style: (feature) => {
                                const colour = colourFor(view.places.get(view.keyOf(feature)).graduates);
                                // Zoomed out to the whole country a city is only a
                                // few pixels wide, and a contrasting border would
                                // swallow its fill, so it's outlined in its own
                                // colour. Regions are big enough for a white line
                                // between neighbours, and a Regional Admin's own
                                // keeps its heavier outline.
                                if (mode === 'city') {
                                    return { fillColor: colour, fillOpacity: 0.85, color: colour, weight: 1, opacity: 1 };
                                }
                                const own = feature.properties.region === onlyRegion;
                                return { fillColor: colour, fillOpacity: 0.85, color: own ? '#152A4E' : '#ffffff', weight: own ? 2 : 1, opacity: 1 };
                            },
                            onEachFeature: (feature, place) => {
                                const figures = view.places.get(view.keyOf(feature));
                                place.bindTooltip(tooltipFor(figures, view.subtitle(figures)), {
                                    sticky: true,
                                    // Beside the cursor, flipping sides near the
                                    // edge — above it got clipped near the top.
                                    direction: 'auto',
                                    offset: [12, 0],
                                    className: 'city-map-tooltip',
                                });
                                place.on('mouseover', () => {
                                    place.setStyle({ color: '#152A4E', weight: 2.5 });
                                    place.bringToFront();
                                });
                                place.on('mouseout', () => layer.resetStyle(place));
                            },
                        }).addTo(map);
                        shading = layer;
                    };
                    show();

                    // Framed once, on the view the map opened in — switching
                    // views keeps wherever the viewer has zoomed to.
                    if (shading.getLayers().length) {
                        map.fitBounds(shading.getBounds(), { padding: [24, 24], maxZoom: 10 });
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
    <div id="{{ $mapId }}" data-city-map="{{ json_encode($cityTotals['cities']) }}" data-region-map="{{ json_encode($cityTotals['regions']) }}" data-only-region="{{ $onlyRegion }}"
        style="height: {{ $height }}px; border-radius: 0.5rem;"></div>
    <div class="city-map-mode" data-city-map-mode="{{ $mapId }}" role="group" aria-label="{{ __('Shade the map by') }}">
        <button type="button" data-mode="region" aria-pressed="false">{{ __('Region') }}</button>
        <button type="button" data-mode="city" aria-pressed="true">{{ __('City') }}</button>
    </div>
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
