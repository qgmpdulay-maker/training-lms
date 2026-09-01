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
        'slug' => 'community-based-drrm',
        'title' => 'Community-Based Disaster Risk Reduction and Management',
        'category' => 'Community Resilience',
        'hours' => 12,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'rapid-damage-assessment-and-needs-analysis',
        'title' => 'Rapid Damage Assessment and Needs Analysis',
        'category' => 'Assessment & Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'public-service-continuity-planning',
        'title' => 'Public Service Continuity Planning',
        'category' => 'Continuity Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'post-disaster-needs-assessment',
        'title' => 'Post-Disaster Needs Assessment',
        'category' => 'Assessment & Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'local-drrm-planning',
        'title' => 'Local Disaster Risk Reduction and Management Planning',
        'category' => 'Planning',
        'hours' => 16,
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
        'slug' => 'exercise-design',
        'title' => 'Exercise Design',
        'category' => 'Preparedness',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'emergency-operations-center',
        'title' => 'Emergency Operations Center',
        'category' => 'Emergency Response',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'contingency-planning',
        'title' => 'Contingency Planning',
        'category' => 'Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'basic-disaster-concepts',
        'title' => 'Basic Disaster Concepts',
        'category' => 'DRRM Core',
        'hours' => 4,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'philippine-drrm-system',
        'title' => 'Philippine Disaster Risk Reduction and Management System',
        'category' => 'DRRM Core',
        'hours' => 4,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    ],

];
