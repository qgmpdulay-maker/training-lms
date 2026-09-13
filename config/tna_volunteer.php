<?php

/**
 * Digitized content of the OCD "Volunteers Baseline Data FY 2026" Google
 * Form. Keep this in sync with the source form — do not reword items, since
 * they are validated against verbatim on submission. "Medical Team" is
 * intentionally listed twice under specializations, matching the source
 * form's duplicate entry.
 */
return [

    // This form's own region enumeration — distinct from config('regions.list')
    // used elsewhere in the app (different splits: IVA/IVB vs MIMAROPA, plus
    // BAR/CARAGA/NIR), so it's kept separate rather than reused.
    'regions' => [
        'Region I', 'Region II', 'Region III', 'Region IVA', 'Region IVB', 'Region V',
        'Region VI', 'Region VII', 'Region VIII', 'Region IX', 'Region X', 'Region XI',
        'Region XII', 'BAR', 'CAR', 'CARAGA', 'NCR', 'NIR',
    ],

    'levels' => ['Municipal', 'City', 'Provincial', 'Regional', 'National'],

    'accreditation_statuses' => ['Accredited', 'Non-Accredited'],

    // Each entry becomes its own free-text "number of organized
    // individual/functional teams/units" field, in this order.
    'specializations' => [
        'Vehicular Accident Extrication',
        'Water Search and Rescue',
        'Mountain Search and Rescue',
        'Medical Team',
        'Medical Team',
        'Incident Management Team',
    ],

    'trainings' => [
        'Basic Incident Command System',
        'Integrated Planning Course on Incident Command System',
        'Emergency Operations Center',
        'Community-Based Disaster Risk Reduction and Management',
        'Basic Life Support/ Standard First Aid Training',
    ],

    'mobilization_readiness' => [
        'Immediate Deployment',
        'within 6 to 12 hours',
        'within 12 to 24 hours',
        'more than 24 hours',
    ],

];
