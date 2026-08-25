<?php

/**
 * Placeholder OCD Regional Office list, used to scope regional admin accounts.
 *
 * TODO: Replace with data from a Region model/table if regions ever need
 * their own attributes (contact info, boundaries, etc).
 */
return [
    'list' => [
        'NCR',
        'CAR',
        'Region I',
        'Region II',
        'Region III',
        'Region IV-A',
        'MIMAROPA',
        'Region V',
        'Region VI',
        'Region VII',
        'Region VIII',
        'Region IX',
        'Region X',
        'Region XI',
        'Region XII',
        'Region XIII',
        'BARMM',
    ],

    /**
     * Maps the "OCD Regional Office" option a participant picks at registration
     * (resources/views/auth/register.blade.php) to the matching entry in `list`
     * above, so participants can be scoped to a region the same way admins are.
     *
     * NIR isn't in `list` yet (no matching regional admin scope exists), so it's
     * left unmapped — those participants stay regionless until that's resolved.
     */
    'agency_map' => [
        'OCD-NCR: National Capital Region' => 'NCR',
        'OCD-CAR: Cordillera Administrative Region' => 'CAR',
        'OCD-Region I: Ilocos Region' => 'Region I',
        'OCD-Region II: Cagayan Valley' => 'Region II',
        'OCD-Region III: Central Luzon' => 'Region III',
        'OCD-Region IV-A: CALABARZON' => 'Region IV-A',
        'OCD-Region IV-B: MIMAROPA' => 'MIMAROPA',
        'OCD-Region V: Bicol Region' => 'Region V',
        'OCD-Region VI: Western Visayas' => 'Region VI',
        'OCD-Region VII: Central Visayas' => 'Region VII',
        'OCD-Region VIII: Eastern Visayas' => 'Region VIII',
        'OCD-Region IX: Zamboanga Peninsula' => 'Region IX',
        'OCD-Region X: Northern Mindanao' => 'Region X',
        'OCD-Region XI: Davao Region' => 'Region XI',
        'OCD-Region XII: SOCCSKSARGEN' => 'Region XII',
        'OCD-Region XIII: Caraga' => 'Region XIII',
    ],

    /**
     * Approximate [latitude, longitude] centre of each region, used by the
     * Training Graduates Map to plot trainings that weren't encoded with
     * their own coordinates.
     */
    'geo' => [
        'Region I' => [16.6159, 120.3166],
        'Region II' => [16.9754, 121.8107],
        'Region III' => [15.4828, 120.7120],
        'Region IV-A' => [14.1008, 121.0794],
        'MIMAROPA' => [12.4293, 121.0349],
        'Region V' => [13.4209, 123.4137],
        'Region VI' => [10.9971, 122.5711],
        'Region VII' => [10.0000, 123.6000],
        'Region VIII' => [11.4000, 124.9000],
        'Region IX' => [7.8383, 123.2968],
        'Region X' => [8.4822, 124.6472],
        'Region XI' => [7.0731, 125.6128],
        'Region XII' => [6.2707, 124.6857],
        'Region XIII' => [8.9475, 125.5406],
        'NCR' => [14.5995, 120.9842],
        'CAR' => [17.3513, 121.1719],
        'BARMM' => [7.2078, 124.2500],
    ],
];
