<?php

/**
 * Placeholder training catalog and related settings.
 *
 * TODO: Replace `catalog` with data from a Training model/table once
 * course details are finalized. Keep the `slug` values unique and
 * stable — training requests reference a training by slug.
 */
return [

    // Where new training request notifications are sent (see TOR: training@ocd.gov.ph).
    'notify_email' => env('TRAINING_REQUEST_EMAIL', 'training@ocd.gov.ph'),

    'catalog' => [
    [
        'slug' => 'drrm-fundamentals',
        'title' => 'Disaster Risk Reduction and Management Fundamentals',
        'category' => 'DRRM Core',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'incident-command-system',
        'title' => 'Incident Command System (ICS)',
        'category' => 'Emergency Response',
        'hours' => 16,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'community-based-drr',
        'title' => 'Community-Based Disaster Risk Reduction',
        'category' => 'Community Resilience',
        'hours' => 12,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'search-and-rescue',
        'title' => 'Search and Rescue Operations',
        'category' => 'Emergency Response',
        'hours' => 24,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'psychological-first-aid',
        'title' => 'Psychological First Aid',
        'category' => 'Health & Welfare',
        'hours' => 6,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'camp-coordination-management',
        'title' => 'Camp Coordination and Camp Management',
        'category' => 'Emergency Response',
        'hours' => 10,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    ],

];
