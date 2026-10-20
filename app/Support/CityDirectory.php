<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Every Philippine city and municipality the Graduates by Location map can
 * draw, and the matching from a person's free-text City ("Angeles, Pampanga",
 * "Quezon City, Metro Manila", "City of Biñan") to one of them.
 *
 * The list is built by `npm run geo:build` (scripts/build-ph-city-boundaries.mjs)
 * from the PSA's PSGC boundaries. Each id is the city's PSGC code and lines
 * up with its outline in public/geo/ph-cities.json, so the map only needs an
 * id to shade a city.
 */
class CityDirectory
{
    /** @var array<int, array{id: int, name: string, province: string, region: string}>|null */
    private static ?array $cities = null;

    /** @var array<string, array<int, array{id: int, name: string, province: string, region: string, is_city: bool}>>|null */
    private static ?array $byName = null;

    /**
     * How people write province names that the list spells differently.
     */
    private const PROVINCE_ALIASES = [
        'ncr' => 'metro manila',
        'national capital region' => 'metro manila',
        'metropolitan manila' => 'metro manila',
        'manila' => 'metro manila',
        'compostela valley' => 'davao de oro',
        'western samar' => 'samar',
    ];

    /**
     * @return array<int, array{id: int, name: string, province: string, region: string}>
     */
    public static function all(): array
    {
        if (self::$cities === null) {
            self::$cities = [];

            foreach (json_decode(file_get_contents(resource_path('data/ph-cities.json')), true) as [$id, $name, $province, $region]) {
                self::$cities[$id] = compact('id', 'name', 'province', 'region');
            }
        }

        return self::$cities;
    }

    /**
     * @return array{id: int, name: string, province: string, region: string}|null
     */
    public static function find(int $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    /**
     * How a city reads when picked from the list: "City of Malolos, Bulacan".
     */
    public static function label(array $city): string
    {
        return $city['name'].', '.$city['province'];
    }

    /**
     * Every city as label(), for the type-ahead City fields.
     *
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return collect(self::all())->map(fn (array $city) => self::label($city))->sort()->values()->all();
    }

    /**
     * label() => region code for every city, so picking a city on the
     * registration page can fill in its Region.
     *
     * @return array<string, string>
     */
    public static function regionsByLabel(): array
    {
        return collect(self::all())->mapWithKeys(fn (array $city) => [self::label($city) => $city['region']])->all();
    }

    /**
     * The city a free-text City value means, or null when it isn't a Philippine
     * city or municipality, or could be more than one of them.
     *
     * Anything after the last comma is read as the province. "City of X",
     * "X City" and plain "X" are the same name, but a value that says "City"
     * prefers a city over a same-named municipality (Quezon City over Quezon,
     * Palawan). A tie left after the province is broken by $region, the
     * person's own region.
     *
     * @return array{id: int, name: string, province: string, region: string}|null
     */
    public static function match(?string $value, ?string $region = null): ?array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $hasProvince = str_contains($value, ',');
        [$base, $saysCity] = self::nameKey($hasProvince ? Str::beforeLast($value, ',') : $value);
        $candidates = self::byName()[$base] ?? [];

        if (count($candidates) > 1 && $saysCity) {
            $candidates = self::narrow($candidates, fn (array $city) => $city['is_city']);
        }

        if (count($candidates) > 1 && $hasProvince) {
            $province = self::provinceKey(Str::afterLast($value, ','));
            $candidates = self::narrow($candidates, fn (array $city) => self::provinceKey($city['province']) === $province);
        }

        if (count($candidates) > 1 && $region) {
            $candidates = self::narrow($candidates, fn (array $city) => $city['region'] === $region);
        }

        if (count($candidates) !== 1) {
            return null;
        }

        return self::find(reset($candidates)['id']);
    }

    /**
     * Keeps only the candidates passing $test — unless none do, in which case
     * the hint was probably a typo and the candidates stand as they were.
     */
    private static function narrow(array $candidates, callable $test): array
    {
        $kept = array_filter($candidates, $test);

        return $kept === [] ? $candidates : $kept;
    }

    /**
     * @return array<string, array<int, array{id: int, name: string, province: string, region: string, is_city: bool}>>
     */
    private static function byName(): array
    {
        if (self::$byName === null) {
            self::$byName = [];

            foreach (self::all() as $city) {
                [$base, $isCity] = self::nameKey($city['name']);
                self::$byName[$base][] = $city + ['is_city' => $isCity];
            }
        }

        return self::$byName;
    }

    /**
     * A name reduced to what identifies it — "City of Biñan", "Biñan City" and
     * "Binan" all become "binan" — plus whether it said "City".
     *
     * @return array{0: string, 1: bool}
     */
    private static function nameKey(string $name): array
    {
        $key = self::normalize($name);
        $isCity = false;

        foreach (['city of ', 'municipality of '] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                $isCity = $isCity || $prefix === 'city of ';
                $key = substr($key, strlen($prefix));
            }
        }

        if (str_ends_with($key, ' city')) {
            $isCity = true;
            $key = substr($key, 0, -5);
        }

        return [$key, $isCity];
    }

    private static function provinceKey(string $province): string
    {
        $key = self::normalize($province);

        return self::PROVINCE_ALIASES[$key] ?? $key;
    }

    private static function normalize(string $value): string
    {
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', Str::lower(Str::ascii($value)));
        $value = trim(preg_replace('/\s+/', ' ', $value));

        // Common abbreviations: "Sto. Tomas", "Sta. Rosa", "Gen. Trias".
        return preg_replace(['/\bsto\b/', '/\bsta\b/', '/\bgen\b/'], ['santo', 'santa', 'general'], $value);
    }
}
