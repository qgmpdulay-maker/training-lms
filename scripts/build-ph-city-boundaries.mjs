/**
 * Builds the city/municipality outlines and lookup list behind the dashboards'
 * Graduates by Location map:
 *
 *   npm run geo:build
 *
 * Source: the PSA's PSGC boundaries as published by
 * github.com/altcoder/philippines-psgc-shapefiles (cleaned OCHA COD-AB
 * shapes matched to current PSGC codes and names) and rendered to GeoJSON by
 * github.com/faeldon/philippines-json-maps. Both MIT. The newest year folder
 * in faeldon's repo is used, so a new PSGC release is picked up on its own.
 *
 * Writes:
 *   public/geo/ph-cities.json      city outlines, each tagged only with its PSGC code
 *   public/geo/ph-regions.json     region outlines, tagged with the app's region code
 *   resources/data/ph-cities.json  [psgc, name, province, region] per city, for
 *                                  App\Support\CityDirectory
 *   storage/app/geo-source/report.md  what changed against the previous list
 *
 * The source files (about 60 MB) are cached in storage/app/geo-source and only
 * re-downloaded when GitHub says they changed. Set GITHUB_TOKEN to lift the
 * API's 60-requests-an-hour limit (the GitHub Action does).
 *
 * Outlines are simplified with mapshaper on the shared topology, so two
 * neighbouring cities keep one border between them instead of each being
 * thinned on its own and drifting into overlaps and gaps.
 */

import { existsSync } from 'node:fs';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import mapshaper from 'mapshaper';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const CACHE = join(ROOT, 'storage/app/geo-source');

const MAPS_REPO = 'faeldon/philippines-json-maps';
const PSGC_CSV = 'https://raw.githubusercontent.com/altcoder/philippines-psgc-shapefiles/main/dist/PH_Adm2_ProvDists.csv';

// Largest distance, in metres, an outline may move when it's thinned. About
// a pixel at the zoom the dashboard map opens a single region at.
const CITY_INTERVAL = 40;
const REGION_INTERVAL = 250;

// PSGC region code (adm1) to the app's own region codes (config/regions.php).
const REGIONS = {
    100000000: 'Region I',
    200000000: 'Region II',
    300000000: 'Region III',
    400000000: 'Region IV-A',
    500000000: 'Region V',
    600000000: 'Region VI',
    700000000: 'Region VII',
    800000000: 'Region VIII',
    900000000: 'Region IX',
    1000000000: 'Region X',
    1100000000: 'Region XI',
    1200000000: 'Region XII',
    1300000000: 'NCR',
    1400000000: 'CAR',
    1600000000: 'Region XIII',
    1700000000: 'MIMAROPA',
    1900000000: 'BARMM',
};

// Highly urbanized cities are their own "province" in the PSGC, so the
// province list has no name for them, and the per-province city files leave
// them out. These are their names and the provinces they sit in, which is how
// people write them ("Angeles, Pampanga"). A new one stops the build until
// it's added here, rather than going out with a blank province.
const HUC_PROVINCES = {
    330100000: ['City of Angeles', 'Pampanga'],
    331400000: ['City of Olongapo', 'Zambales'],
    431200000: ['City of Lucena', 'Quezon'],
    630200000: ['City of Bacolod', 'Negros Occidental'],
    631000000: ['City of Iloilo', 'Iloilo'],
    730600000: ['City of Cebu', 'Cebu'],
    731100000: ['City of Lapu-Lapu', 'Cebu'],
    731300000: ['City of Mandaue', 'Cebu'],
    831600000: ['City of Tacloban', 'Leyte'],
    931700000: ['City of Zamboanga', 'Zamboanga del Sur'],
    1030500000: ['City of Cagayan de Oro', 'Misamis Oriental'],
    1030900000: ['City of Iligan', 'Lanao del Norte'],
    1130700000: ['City of Davao', 'Davao del Sur'],
    1230800000: ['City of General Santos', 'South Cotabato'],
    1430300000: ['City of Baguio', 'Benguet'],
    1630400000: ['City of Butuan', 'Agusan del Norte'],
    1731500000: ['City of Puerto Princesa', 'Palawan'],
};

// Province names as people write them, where the PSGC's own differ.
const PROVINCE_LABELS = {
    'City of Isabela (Not a Province)': 'Basilan',
};

// The eight BARMM clusters carved out of Cotabato province, which have no
// province of their own.
const SPECIAL_GEOGRAPHIC_AREA = 1999900000;

const headers = {
    'User-Agent': 'training-lms-geo-build',
    ...(process.env.GITHUB_TOKEN ? { Authorization: `Bearer ${process.env.GITHUB_TOKEN}` } : {}),
};

async function get(url, as = 'json') {
    const response = await fetch(url, { headers });
    if (!response.ok) {
        throw new Error(`${response.status} ${response.statusText} fetching ${url}`);
    }
    return as === 'json' ? response.json() : response.text();
}

/** Downloads every listed file not already cached at the same git blob sha. */
async function sync(files, folder) {
    await mkdir(folder, { recursive: true });
    const manifestPath = join(folder, 'manifest.json');
    const manifest = existsSync(manifestPath) ? JSON.parse(await readFile(manifestPath, 'utf8')) : {};
    const stale = files.filter((file) => manifest[file.name] !== file.sha || !existsSync(join(folder, file.name)));

    let done = 0;
    const queue = [...stale];
    await Promise.all(Array.from({ length: 8 }, async () => {
        for (let file = queue.shift(); file; file = queue.shift()) {
            await writeFile(join(folder, file.name), await get(file.download_url, 'text'));
            manifest[file.name] = file.sha;
            process.stdout.write(`\r  downloaded ${++done}/${stale.length}`);
        }
    }));
    if (stale.length) {
        process.stdout.write('\n');
    }
    await writeFile(manifestPath, JSON.stringify(manifest, null, 2));

    return Promise.all(files.map(async (file) => JSON.parse(await readFile(join(folder, file.name), 'utf8'))));
}

function parseCsv(text) {
    const rows = text.trim().split(/\r?\n/).map((line) => line.match(/("([^"]|"")*"|[^,]*)(,|$)/g)
        .map((cell) => cell.replace(/,$/, '').replace(/^"|"$/g, '').replace(/""/g, '"')));
    const [head, ...body] = rows;
    return body.map((row) => Object.fromEntries(head.map((key, i) => [key, row[i]])));
}

function provinceOf(props, provinces) {
    if (props.adm1_psgc === 1300000000) {
        return 'Metro Manila';
    }
    if (props.adm2_psgc === SPECIAL_GEOGRAPHIC_AREA) {
        return 'Special Geographic Area';
    }
    if (HUC_PROVINCES[props.adm2_psgc]) {
        return HUC_PROVINCES[props.adm2_psgc][1];
    }
    const name = provinces[props.adm2_psgc];
    if (!name) {
        throw new Error(`No province for ${props.adm3_en} (PSGC ${props.adm3_psgc}). If it's a new highly urbanized city, add it to HUC_PROVINCES.`);
    }
    return PROVINCE_LABELS[name] ?? name;
}

// The source title-cases blindly: "Brooke'S Point", "Abra De Ilog".
const cleanName = (name) => name.trim().replace(/\s+/g, ' ').replace(/'S\b/g, "'s").replace(/ (De|Del|De La) /g, (word) => word.toLowerCase());

const label = ([, name, province]) => `${name}, ${province}`;

function report(before, after, source) {
    const old = new Set(before.map(label));
    const now = new Set(after.map(label));
    const added = [...now].filter((city) => !old.has(city)).sort();
    const removed = [...old].filter((city) => !now.has(city)).sort();
    const list = (cities) => cities.map((city) => `- ${city}`).join('\n') || '_None._';

    return `# City boundary refresh

Source: \`${MAPS_REPO}\` ${source}, ${after.length} cities and municipalities.

Saved home cities are matched by name, so a renamed or removed city below
can stop some graduates showing on the map. After merging, check with
\`php artisan tinker\` or the Deployments page's "no home city" notice.

## Added or renamed (${added.length})
${list(added)}

## Removed or renamed (${removed.length})
${list(removed)}
`;
}

async function main() {
    // One call lists every file in the repo, with each one's git blob sha.
    const tree = (await get(`https://api.github.com/repos/${MAPS_REPO}/git/trees/master?recursive=1`)).tree
        .filter((entry) => entry.type === 'blob')
        .map((entry) => ({
            path: entry.path,
            name: entry.path.split('/').at(-1),
            sha: entry.sha,
            download_url: `https://raw.githubusercontent.com/${MAPS_REPO}/master/${entry.path}`,
        }));
    const year = tree.map((file) => file.path.split('/')[0]).filter((top) => /^\d{4}$/.test(top)).sort().at(-1);
    const inFolder = (folder, prefix) => tree.filter((file) => file.path.startsWith(`${year}/geojson/${folder}/hires/${prefix}`));
    console.log(`Using ${MAPS_REPO} ${year}`);

    // A file whose province has no shapes yet is a bare GeometryCollection.
    const featuresOf = (collections) => collections.flatMap((collection) => collection.features ?? []);
    const cityFeatures = featuresOf(await sync(inFolder('provdists', 'municities-provdist-'), join(CACHE, year)));
    const provinces = Object.fromEntries(parseCsv(await get(PSGC_CSV, 'text')).map((row) => [Number(row.adm2_psgc), row.adm2_en.trim()]));

    // A highly urbanized city is a province-level unit in the PSGC, so the
    // per-province city files leave it out. Its barangays are published,
    // though, and merged they make the city's outline.
    const haveCity = new Set(cityFeatures.map((feature) => feature.properties.adm3_psgc));
    const missing = Object.keys(HUC_PROVINCES).map(Number).filter((code) => !haveCity.has(code));
    const barangayFiles = missing.flatMap((code) => inFolder('municities', `bgysubmuns-municity-${code}.`));
    const barangays = featuresOf(await sync(barangayFiles, join(CACHE, year)));
    const merged = barangays.length ? JSON.parse((await mapshaper.applyCommands(
        '-i barangays.json snap -dissolve adm3_psgc copy-fields=adm1_psgc,adm2_psgc -o merged.json format=geojson',
        { 'barangays.json': { type: 'FeatureCollection', features: barangays } },
    ))['merged.json']).features : [];
    for (const feature of merged) {
        feature.properties.adm3_en = HUC_PROVINCES[feature.properties.adm3_psgc]?.[0] ?? '';
    }
    const unmapped = missing.filter((code) => !merged.some((feature) => feature.properties.adm3_psgc === code));
    if (unmapped.length) {
        throw new Error(`No outline found for PSGC ${unmapped.join(', ')}.`);
    }

    const directory = [];
    const features = [];
    for (const feature of [...cityFeatures, ...merged]) {
        const props = feature.properties;
        const region = REGIONS[props.adm1_psgc];
        if (!region) {
            throw new Error(`Unknown PSGC region ${props.adm1_psgc} for ${props.adm3_en}. Add it to REGIONS (and config/regions.php).`);
        }
        directory.push([props.adm3_psgc, cleanName(props.adm3_en), provinceOf(props, provinces), region]);
        features.push({ type: 'Feature', properties: { id: props.adm3_psgc, region }, geometry: feature.geometry });
    }
    directory.sort((a, b) => a[3].localeCompare(b[3], 'en') || a[2].localeCompare(b[2], 'en') || a[1].localeCompare(b[1], 'en'));

    const ids = new Set(directory.map(([id]) => id));
    if (ids.size !== directory.length) {
        throw new Error('The same PSGC code appears twice in the source.');
    }

    console.log(`Simplifying ${features.length} outlines...`);
    const output = await mapshaper.applyCommands([
        '-i source.json snap',
        `-simplify interval=${CITY_INTERVAL} keep-shapes`,
        '-clean',
        '-filter-fields id,region',
        '-dissolve region + name=regions',
        `-simplify interval=${REGION_INTERVAL} keep-shapes target=regions`,
        '-o target=source cities.json format=geojson precision=0.0001',
        '-o target=regions regions.json format=geojson precision=0.0001',
    ].join(' '), { 'source.json': { type: 'FeatureCollection', features } });

    // Outlines carry only the id; everything else about a city lives in the
    // directory, which the server reads.
    const cities = JSON.parse(output['cities.json']);
    cities.features.forEach((feature) => { feature.properties = { id: feature.properties.id }; });

    const directoryPath = join(ROOT, 'resources/data/ph-cities.json');
    const before = existsSync(directoryPath) ? JSON.parse(await readFile(directoryPath, 'utf8')) : [];

    const write = async (path, data) => {
        const text = typeof data === 'string' ? data : JSON.stringify(data);
        await mkdir(dirname(join(ROOT, path)), { recursive: true });
        await writeFile(join(ROOT, path), text);
        console.log(`${path}: ${Math.round(Buffer.byteLength(text) / 1024)} KB`);
    };

    await write('public/geo/ph-cities.json', cities);
    await write('public/geo/ph-regions.json', output['regions.json'].toString());
    await write('resources/data/ph-cities.json', directory);

    const summary = report(before, directory, year);
    await write('storage/app/geo-source/report.md', summary);
    console.log(`${directory.length} cities and municipalities`);
}

main().catch((error) => {
    console.error(error.message);
    process.exit(1);
});
