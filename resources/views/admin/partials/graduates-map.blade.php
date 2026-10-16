{{--
    The Graduates by Location map: LGU/NGA markers over region boundaries —
    hover a marker for its details, or anywhere in a region for its totals.
    Shared by the standalone Graduates Map page and the Regional Admin
    dashboard so the two are the same map. (Super Admin's dashboard keeps its
    own copy in dashboard.blade.php, since it redraws on every filter change.)

    Expects $mapId, $points (MonitoringController::mapPoints()),
    $regionDeployed (MonitoringController::deployedByRegion()), and optionally
    $height and $onlyRegion — a Regional Admin's own region, which draws just
    that region's boundary and zooms to it instead of shading every region by
    graduate count.
--}}
@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

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
        .graduates-map-panel .leaflet-container {
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
@endonce

<div class="graduates-map-panel px-3 pb-3">
    <div id="{{ $mapId }}" style="height: {{ $height ?? 520 }}px; border-radius: 0.5rem;"></div>
</div>

<script>
    (function () {
        const points = @json($points);
        const regionDeployed = @json((object) $regionDeployed);
        const onlyRegion = @json($onlyRegion ?? null);
        const map = L.map(@json($mapId), { attributionControl: false, scrollWheelZoom: false }).setView([12.8797, 121.7740], 5);

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
            // The bubble shows the graduates inside it, not how many
            // locations it groups — so with a training picked, the number on
            // the map is that training's graduates. Location count stays on
            // hover via the title.
            iconCreateFunction: (cluster) => {
                const locations = cluster.getChildCount();
                const graduates = cluster.getAllChildMarkers().reduce((total, m) => total + (m.options.graduates || 0), 0);
                const label = graduates >= 1000 ? (graduates / 1000).toFixed(1).replace(/\.0$/, '') + 'k' : String(graduates);
                const size = label.length <= 2 ? 32 : (label.length === 3 ? 40 : 48);
                const title = graduates.toLocaleString() + ' {{ __('graduates') }} · ' + locations + ' {{ __('locations') }}';
                return L.divIcon({
                    html: '<div class="graduates-cluster-badge" title="' + title + '" style="width:' + size + 'px;height:' + size + 'px;line-height:' + size + 'px;">' + label + '</div>',
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
                graduates: point.graduates,
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
                '<div style="color:#374151">' + '{{ __('Deployed') }}: <strong>' + point.deployed + '</strong></div>' +
                '<div style="color:#374151">' + '{{ __('Teams organized') }}: <strong>' + point.teams + '</strong></div>' +
                '<div style="color:#374151">' + '{{ __('Trainings') }}: <strong>' + point.trainings + '</strong></div>',
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
        const regionKeyFor = (feature) => regionNameMap[feature.properties.REGION] ?? feature.properties.REGION;

        const regionTotals = {};
        points.forEach((point) => {
            if (!point.region) {
                return;
            }
            regionTotals[point.region] ??= { graduates: 0, trainings: 0, deployed: regionDeployed[point.region] ?? 0 };
            regionTotals[point.region].graduates += point.graduates;
            regionTotals[point.region].trainings += point.trainings;
        });

        const REGION_ACCENT = '#3B82F6';
        const REGION_BORDER_IDLE = 'rgba(21, 42, 78, 0.22)';
        const opacityScale = [0.22, 0.4, 0.55, 0.75];
        const maxRegionGraduates = Math.max(1, ...Object.values(regionTotals).map((r) => r.graduates), 1);

        const opacityFor = (graduates) => {
            if (onlyRegion) {
                // One region on show, so there's nothing to rank it against —
                // a flat tint that leaves the towns underneath readable.
                return 0.25;
            }
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
                    // A Regional Admin only sees their own region's numbers, so
                    // only their region is drawn — the rest would just read 0.
                    filter: (feature) => !onlyRegion || regionKeyFor(feature) === onlyRegion,
                    style: (feature) => {
                        const data = regionTotals[regionKeyFor(feature)];
                        const active = !!onlyRegion || !!(data && data.graduates);

                        return {
                            fillColor: REGION_ACCENT,
                            fillOpacity: active ? opacityFor(data ? data.graduates : 0) : 0,
                            color: active ? REGION_ACCENT : REGION_BORDER_IDLE,
                            weight: active ? 1.5 : 1,
                        };
                    },
                    onEachFeature: (feature, featureLayer) => {
                        const key = regionKeyFor(feature);
                        const data = regionTotals[key] || { graduates: 0, trainings: 0, deployed: regionDeployed[key] ?? 0 };
                        const active = !!onlyRegion || !!data.graduates;
                        const baseOpacity = active ? opacityFor(data.graduates) : 0;
                        const baseWeight = active ? 1.5 : 1;
                        const baseColor = active ? REGION_ACCENT : REGION_BORDER_IDLE;

                        featureLayer.bindTooltip(
                            '<div style="font-size:12px;min-width:150px">' +
                            '<div style="font-weight:700;color:#152A4E;letter-spacing:.02em;margin-bottom:3px">' + key + '</div>' +
                            '<div style="color:#6b7280">{{ __('Graduates') }}: <strong style="color:' + REGION_ACCENT + '">' + data.graduates + '</strong></div>' +
                            '<div style="color:#6b7280">{{ __('Deployed') }}: <strong style="color:' + REGION_ACCENT + '">' + data.deployed + '</strong></div>' +
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

                // Frame the whole assigned region rather than just its markers.
                // (OCD Central has no boundary in the dataset, so it keeps the
                // marker framing above.)
                if (onlyRegion && layer.getLayers().length > 0) {
                    map.fitBounds(layer.getBounds(), { padding: [20, 20] });
                }
            })
            .catch(() => {});

        setTimeout(() => map.invalidateSize(), 200);
    })();
</script>
