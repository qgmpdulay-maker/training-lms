<?php

/**
 * Digitized content of the OCD "Baseline Data Tool for Responders
 * Competency-Capability Assessment in the Local Government Units" Google
 * Form. Keep this in sync with the source form — do not reword items, since
 * they are validated against verbatim on submission. Obvious typos in the
 * source ("Glovess", "Hack Shaw", "Aparatus", "appicable", "erxtrication")
 * are preserved verbatim rather than corrected.
 */
return [

    'lgu_levels' => ['Provincial', 'City', 'Municipal', 'Barangay'],

    'hazards' => [
        'Flooding (water related)',
        'Earthquake',
        'Tsunami',
        'Landslide',
        'CBRN/HAZMAT',
        'Fire',
        'Vehicular Accidents',
    ],

    'hazard_ranks' => ['Rank #1', 'Rank #2', 'Rank #3', 'NONE'],

    // Risk level => people per responder (baseline responders = population / ratio_per_responder).
    'risk_levels' => [
        'Low Risk' => [
            'ratio_per_responder' => 1500,
            'description' => 'Areas with limited hazard exposure, low population density, and minimal operational complexity, where emergency incidents are infrequent and manageable with minimal resources (Use 1:1,500)',
        ],
        'Moderate Risk' => [
            'ratio_per_responder' => 1000,
            'description' => 'Areas with recurring exposure to common hazards and moderate population density, requiring organized and sustained response capability. (Use 1:1,000)',
        ],
        'High Risk' => [
            'ratio_per_responder' => 750,
            'description' => 'Areas with high hazard exposure, high population density, and complex operational environment, requiring rapid, multi-team, and specialized response capability. (Use 1:750)',
        ],
        'Very High Risk' => [
            'ratio_per_responder' => 500,
            'description' => 'Areas with extreme hazard exposure and very high population density, where large-scale incidents are highly probable and may require national-level augmentation. (Use 1:500)',
        ],
    ],

    // Disciplines rated for team size (Organized Team/s for Deployment) — no "Others" row.
    'organized_team_disciplines' => [
        'WASAR', 'USAR', 'MOSAR', 'Vehicular Accident Extrication', 'Medical Team', 'IMT', 'EOC',
    ],

    // Disciplines rated for Personnel Competency and Team Capability — includes "Others".
    'capability_disciplines' => [
        'USAR Teams', 'WASAR', 'MOSAR', 'Vehicular Accident Extrication', 'Medical Team', 'IMT', 'EOC', 'Others',
    ],

    'team_sizes' => [
        'N/A',
        'Crew (4-6)',
        'Squad (8-12)',
        'Team/Section (16-21)',
        'Unit/Task Group (32-42)',
        'Task Force (64+)',
    ],

    'personnel_competency_levels' => [
        'Basic',
        'Technician',
        'Specialist',
        'Not Applicable',
    ],

    'team_capability_levels' => [
        'Basic',
        'Operational',
        'Advanced',
        'Not Applicable',
        'Under development',
    ],

    // Equipment Capability: each is a checklist ("Not Applicable" included as
    // its own checkbox, per the source form) plus a free-text "other" field.
    'equipment' => [
        'ppe' => [
            'label' => 'Personal Protective Equipment',
            'items' => [
                'Helmet', 'Head Lamp', 'Eye Protection Goggles', 'Ear Protection',
                'Individual First Aid Kits/ Personal Medical Kits', 'Whistle', 'Rescue Glovess',
                'Reflectorized Vest', 'Elbow and Knee Pad', 'Safety Shoes (Water Resistant/ Steel Toe )',
                'Not Applicable',
            ],
        ],
        'search_tools' => [
            'label' => 'Search Tools',
            'items' => [
                'Life Locator', 'Drone', 'Search Camera', 'Sound Detector',
                'K9 Search Dog (as appicable)', 'Not Applicable',
            ],
        ],
        'cssr_hand_tools' => [
            'label' => 'CSSR Hand Tools',
            'items' => [
                'Shovel', 'Claw Bar', 'Claw Hammer', 'Hand Trowel/ Cement Trowel', 'Pry Bar (6 feet)',
                'Sledge Hammer', 'Axe', 'Hack Shaw', 'Pliers', 'Screw Driver', 'Wood Saw/ Crosscut Handsaw',
                'Tin Snip', 'Chisel Pointed (Masonry/ Wood)', 'Chisel Flat (Masonry/Wood)', 'Bolt Cutter',
                'Not Applicable',
            ],
        ],
        'cssr_power_tools' => [
            'label' => 'CSSR Power Tools',
            'items' => [
                'Chainsaw Wood', 'Chainsaw Concrete', 'Acetylene torch with tank', 'Chipping Hammer',
                'Jack Hammer', 'Reciprocating Saw', 'Rotary Rescue Saw (K12)', 'Circular Saw',
                'Drill Hammer with Spare Drill bits', 'Grinder', 'Nail gun 4"- 5 1/8\' nail',
                'Hydraulic Cutter', 'Hydraulic Spreader', 'Hydraulic Combination Tools', 'Hydraulic RAM',
                'Hydraulic Pump', 'Not Applicable',
            ],
        ],
        'shoring_tools' => [
            'label' => 'Shoring Tool',
            'items' => [
                'Hydraulic Shore', 'Pencil', 'Meter Tape', 'L-Square', 'Pivot Square', 'Level Bar',
                'Drill Hammer', 'Picket (1 dia X 4 ft)', 'Not Applicable',
            ],
        ],
        'lifting_tools' => [
            'label' => 'Lifting Stabilizing Moving Tools',
            'items' => [
                'GI Pipe Size 2 in dia X 6 ft', 'Cribbing Blocks 4x4x18in', 'Wedges 4" X 4" X 18"',
                'Wedges 2" X 4" X 12"', 'Shims 1" X 4" X 12"', 'Come Along Winch', 'Not Applicable',
            ],
        ],
        'technical_rope' => [
            'label' => 'Technical Rope',
            'items' => [
                'Static Kernmantle (Black White) 50 m', 'Static Kernmantle (Blue White) 50 m',
                'Dynamic Kernmantle (Red) 80 m, 50 m , 30 m', 'Class 3 Harness', 'Class 2 Harness',
                'Victim harness', 'Webbings (30, 20, 15, 12, 5 ft)', 'Prusik Cord Long (6 ft)',
                'Prusik Cord Small (5.6 ft)', 'Litter Basket', 'Utility Rope (5 meters)', 'Carabiner',
                'Minding, Big, Small, Double, Kootenay Pulley', 'Rescue 8', 'Anchor Plate',
                'Spring Loaded Ascender', 'Edge Protector', 'Rope Bags', 'Chest and Hand Ascender',
                'SKED', 'CMC Ascender', 'Tripod', 'Litter Harness', 'Multi-Purpose Device (MPD)',
                'ASAP Belay', 'Self Breaking Descender (ID)', 'Not Applicable',
            ],
        ],
        'hazmat' => [
            'label' => 'HAZMAT',
            'items' => [
                'HAZMAT Suit Level B', 'HAZMAT Suit Level C', 'Self- Contain Breathing Aparatus Set',
                'Multi-gas Detector', 'Chemical Detector', 'Radiological Detection System',
                'M-50 Gas Mask with Canister Filter', 'HAZMAT Boots', 'HAZMAT Gloves', 'DECON Tent',
                'Not Applicable',
            ],
        ],
        'communication' => [
            'label' => 'Communication',
            'items' => [
                'Portable Two-Way Radios (UHF Handheld) with accessories', 'Base Radio',
                'Satellite Internet Device', 'Satellite Phone (Back Up)', 'Mobile Phone (Back Up)',
                'Visual Communication Tools (Signal Flags/ Handheld Light Signals/ Flashlights)',
                'GPS Tracking Devices', 'Handheld Air band Radio  (for operations that require movement of air)',
                'Not Applicable',
            ],
        ],
        'transport_vehicles' => [
            'label' => 'Transport Vehicle',
            'items' => ['Ambulance', 'Rescue Truck', 'Rescue Van', 'Bus', 'Not Applicable'],
        ],
    ],

    'operational_systems' => [
        'N/A',
        'Response Plan',
        'Operations Center Manual',
        'LDRRM Plan',
        'Contingency Plan',
        'Communication Plan',
        'Operation Protocols and SOPs (Mobilisation, Deployment, Medical, Operation protocols, Demobilization and erxtrication Plan)',
    ],

    'sustainment_items' => [
        'Inventory of stockpiles',
        'Warehouse',
        'Prepositioning Map/Sites',
        'Logistics Plan',
        'Equipment Inventory and Loading Plans',
    ],

];
