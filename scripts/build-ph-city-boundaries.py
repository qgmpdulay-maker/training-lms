"""
Builds the city/municipality outlines and lookup list behind the dashboards'
Graduates by Location map.

Source: geoBoundaries gbOpen PHL ADM1 / ADM2 / ADM3, simplified release
(https://www.geoboundaries.org, data from NAMRIA, PSA and OCHA Philippines,
CC BY 3.0 IGO). Download the three "simplifiedGeometryGeoJSON" files listed
at https://www.geoboundaries.org/api/current/gbOpen/PHL/ADM1/ (and ADM2,
ADM3) into one folder, then run:

    python3 scripts/build-ph-city-boundaries.py <that folder>

Writes:
  public/geo/ph-cities.json    city outlines, each tagged only with an id
  public/geo/ph-regions.json   region outlines, tagged with the app's region code
  resources/data/ph-cities.json  [id, name, province, region] per city, for
                                 App\\Support\\CityDirectory

ADM3 carries no province or region, so each city takes them from whichever
ADM2 / ADM1 outline contains a point inside it. The City of Manila comes as
its separate districts (Tondo, Sampaloc, ...) and is merged back into one.
Outlines are thinned with Douglas-Peucker and rounded to 4 decimals (~11 m)
so the browser download stays around a megabyte.
"""

import json
import math
import os
import sys

TOLERANCE = 0.0025  # degrees, ~280 m
REGION_TOLERANCE = 0.006

REGION_CODES = {
    'ARMM': 'BARMM',
    'Bicol Region': 'Region V',
    'CAR': 'CAR',
    'Cagayan Valley': 'Region II',
    'Calabarzon': 'Region IV-A',
    'Caraga': 'Region XIII',
    'Central Luzon': 'Region III',
    'Central Visayas': 'Region VII',
    'Davao Region': 'Region XI',
    'Eastern Visayas': 'Region VIII',
    'Ilocos Region': 'Region I',
    'Mimaropa': 'MIMAROPA',
    'NCR': 'NCR',
    'Northern Mindanao': 'Region X',
    'Soccsksargen': 'Region XII',
    'Western Visayas': 'Region VI',
    'Zamboanga Peninsula': 'Region IX',
}

# ADM2 names that aren't how anyone would write the province.
PROVINCE_LABELS = {
    'NCR, City of Manila, First District': 'Metro Manila',
    'NCR, Second District': 'Metro Manila',
    'NCR, Third District': 'Metro Manila',
    'NCR, Fourth District': 'Metro Manila',
    'City of Isabela': 'Basilan',
    'Cotabato City': 'Maguindanao',
    'Compostela Valley': 'Davao de Oro',
}

MANILA_PROVINCE = 'NCR, City of Manila, First District'

# The 2020 region outlines predate a change: Cotabato City voted into BARMM
# in 2019 while staying outside it geographically.
REGION_OVERRIDES = {('Cotabato City', 'Maguindanao'): 'BARMM'}


def polygons(geometry):
    if geometry is None:
        return []
    if geometry['type'] == 'Polygon':
        return [geometry['coordinates']]
    if geometry['type'] == 'MultiPolygon':
        return geometry['coordinates']
    return []


def ring_area(ring):
    return sum(x1 * y2 - x2 * y1 for (x1, y1), (x2, y2) in zip(ring, ring[1:])) / 2


def point_in_ring(x, y, ring):
    inside = False
    for (x1, y1), (x2, y2) in zip(ring, ring[1:]):
        if (y1 > y) != (y2 > y) and x < (x2 - x1) * (y - y1) / (y2 - y1) + x1:
            inside = not inside
    return inside


def point_in_polygons(x, y, polys):
    for poly in polys:
        if point_in_ring(x, y, poly[0]) and not any(point_in_ring(x, y, hole) for hole in poly[1:]):
            return True
    return False


def interior_point(polys):
    """A point guaranteed inside the largest polygon: the midpoint of the first
    inside span along the horizontal line through its bounding-box middle."""
    poly = max(polys, key=lambda p: abs(ring_area(p[0])))
    ring = poly[0]
    ys = [p[1] for p in ring]
    y = (min(ys) + max(ys)) / 2
    xs = sorted(
        x1 + (y - y1) * (x2 - x1) / (y2 - y1)
        for (x1, y1), (x2, y2) in zip(ring, ring[1:])
        if (y1 > y) != (y2 > y)
    )
    return ((xs[0] + xs[1]) / 2, y) if len(xs) >= 2 else tuple(ring[0])


def bbox(polys):
    xs = [p[0] for poly in polys for p in poly[0]]
    ys = [p[1] for poly in polys for p in poly[0]]
    return min(xs), min(ys), max(xs), max(ys)


def containing(point, areas):
    x, y = point
    for name, box, polys in areas:
        if box[0] <= x <= box[2] and box[1] <= y <= box[3] and point_in_polygons(x, y, polys):
            return name
    # Coastal points can fall a hair outside a separately simplified outline;
    # take the nearest area instead.
    return min(areas, key=lambda a: math.dist(point, ((a[1][0] + a[1][2]) / 2, (a[1][1] + a[1][3]) / 2)))[0]


def simplify(points, tolerance):
    if len(points) < 3:
        return points
    (x1, y1), (x2, y2) = points[0], points[-1]
    dx, dy = x2 - x1, y2 - y1
    length = math.hypot(dx, dy)
    worst, index = 0, 0
    for i in range(1, len(points) - 1):
        px, py = points[i]
        d = abs(dy * px - dx * py + x2 * y1 - y2 * x1) / length if length else math.hypot(px - x1, py - y1)
        if d > worst:
            worst, index = d, i
    if worst <= tolerance:
        return [points[0], points[-1]]
    return simplify(points[: index + 1], tolerance)[:-1] + simplify(points[index:], tolerance)


def simplify_ring(ring, tolerance):
    # Split closed rings in two so the first/last point isn't a fixed anchor.
    half = len(ring) // 2
    out = simplify(ring[: half + 1], tolerance)[:-1] + simplify(ring[half:], tolerance)
    out = [[round(x, 4), round(y, 4)] for x, y in out]
    deduped = [p for i, p in enumerate(out) if i == 0 or p != out[i - 1]]
    return deduped if len(deduped) >= 4 else None


def simplify_polygons(polys, tolerance):
    kept = []
    for poly in polys:
        outer = simplify_ring(poly[0], tolerance)
        if outer is None:
            continue
        holes = [h for h in (simplify_ring(r, tolerance) for r in poly[1:]) if h]
        kept.append([outer] + holes)
    if not kept:
        # Never lose a whole city to simplification — keep its largest
        # polygon at a finer tolerance.
        largest = max(polys, key=lambda p: abs(ring_area(p[0])))
        kept = [[simplify_ring(largest[0], tolerance / 10) or largest[0]]]
    return kept


def geometry(polys):
    return {'type': 'Polygon', 'coordinates': polys[0]} if len(polys) == 1 else {'type': 'MultiPolygon', 'coordinates': polys}


def main(source):
    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

    def load(level):
        with open(os.path.join(source, f'{level}.geojson'), encoding='utf-8') as fh:
            return json.load(fh)['features']

    regions = [(f['properties']['shapeName'], None, polygons(f['geometry'])) for f in load('ADM1')]
    regions = [(name, bbox(polys), polys) for name, _, polys in regions]
    provinces = [(f['properties']['shapeName'], bbox(polygons(f['geometry'])), polygons(f['geometry'])) for f in load('ADM2')]

    cities = []
    manila = []
    for feature in load('ADM3'):
        polys = polygons(feature['geometry'])
        if not polys:
            continue
        point = interior_point(polys)
        province = containing(point, provinces)
        region = containing(point, regions)
        if province == MANILA_PROVINCE:
            manila.extend(polys)
            continue
        name = feature['properties']['shapeName'].strip()
        province = PROVINCE_LABELS.get(province, province)
        cities.append({
            'name': name,
            'province': province,
            'region': REGION_OVERRIDES.get((name, province), REGION_CODES[region]),
            'polys': polys,
        })

    if manila:
        cities.append({'name': 'City of Manila', 'province': 'Metro Manila', 'region': 'NCR', 'polys': manila})

    cities.sort(key=lambda c: (c['region'], c['province'], c['name']))

    features, directory = [], []
    for index, city in enumerate(cities, start=1):
        features.append({
            'type': 'Feature',
            'properties': {'id': index},
            'geometry': geometry(simplify_polygons(city['polys'], TOLERANCE)),
        })
        directory.append([index, city['name'], city['province'], city['region']])

    region_features = [{
        'type': 'Feature',
        'properties': {'region': REGION_CODES[name]},
        'geometry': geometry(simplify_polygons(polys, REGION_TOLERANCE)),
    } for name, _, polys in regions]

    def write(path, data):
        full = os.path.join(root, path)
        os.makedirs(os.path.dirname(full), exist_ok=True)
        with open(full, 'w', encoding='utf-8') as fh:
            json.dump(data, fh, ensure_ascii=False, separators=(',', ':'))
        print(f'{path}: {os.path.getsize(full) / 1024:.0f} KB')

    write('public/geo/ph-cities.json', {'type': 'FeatureCollection', 'features': features})
    write('public/geo/ph-regions.json', {'type': 'FeatureCollection', 'features': region_features})
    write('resources/data/ph-cities.json', directory)
    print(f'{len(directory)} cities and municipalities')


if __name__ == '__main__':
    if len(sys.argv) != 2:
        sys.exit('usage: python3 scripts/build-ph-city-boundaries.py <folder with ADM1/ADM2/ADM3 .geojson>')
    sys.setrecursionlimit(20000)
    main(sys.argv[1])
