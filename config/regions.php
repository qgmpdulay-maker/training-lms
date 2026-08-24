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
];
