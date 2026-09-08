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

    // Additional placeholders so every category has enough entries to
    // demonstrate the catalog's "3 visible, scroll for more" card carousel.
    [
        'slug' => 'vulnerability-and-capacity-assessment',
        'title' => 'Vulnerability and Capacity Assessment',
        'category' => 'Assessment & Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'hazard-mapping-and-risk-assessment',
        'title' => 'Hazard Mapping and Risk Assessment',
        'category' => 'Assessment & Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'community-based-early-warning-systems',
        'title' => 'Community-Based Early Warning Systems',
        'category' => 'Community Resilience',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'resilient-livelihoods-planning',
        'title' => 'Resilient Livelihoods Planning',
        'category' => 'Community Resilience',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'community-organizing-for-disaster-preparedness',
        'title' => 'Community Organizing for Disaster Preparedness',
        'category' => 'Community Resilience',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'business-continuity-planning-for-lgus',
        'title' => 'Business Continuity Planning for LGUs',
        'category' => 'Continuity Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'critical-infrastructure-continuity-planning',
        'title' => 'Critical Infrastructure Continuity Planning',
        'category' => 'Continuity Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'continuity-of-operations-planning',
        'title' => 'Continuity of Operations Planning (COOP)',
        'category' => 'Continuity Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'introduction-to-climate-change-adaptation',
        'title' => 'Introduction to Climate Change Adaptation',
        'category' => 'DRRM Core',
        'hours' => 4,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'drrm-legal-framework-and-policies',
        'title' => 'DRRM Legal Framework and Policies',
        'category' => 'DRRM Core',
        'hours' => 4,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'search-and-rescue-operations',
        'title' => 'Search and Rescue Operations',
        'category' => 'Emergency Response',
        'hours' => 16,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'first-aid-and-basic-life-support',
        'title' => 'First Aid and Basic Life Support',
        'category' => 'Emergency Response',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'disaster-preparedness-planning',
        'title' => 'Disaster Preparedness Planning',
        'category' => 'Planning',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'recovery-and-rehabilitation-planning',
        'title' => 'Recovery and Rehabilitation Planning',
        'category' => 'Planning',
        'hours' => 16,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'simulation-exercise-facilitation',
        'title' => 'Simulation Exercise Facilitation',
        'category' => 'Preparedness',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'early-warning-system-design',
        'title' => 'Early Warning System Design',
        'category' => 'Preparedness',
        'hours' => 8,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    [
        'slug' => 'community-preparedness-drills',
        'title' => 'Community Preparedness Drills',
        'category' => 'Preparedness',
        'hours' => 4,
        'description' => 'Placeholder description. This will be replaced with the actual course overview once training content has been finalized.',
    ],
    ],

];
