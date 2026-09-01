<?php

namespace Database\Seeders;

use App\Models\TrainingRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates every region with a realistic volume of data — participants,
 * training requests, instructors, evaluations, calendar events, and TNA
 * submissions — so charts and tables across the app look like they would at
 * real nationwide scale, instead of the handful of records the earlier demo
 * seeders leave behind. Pure bulk `DB::table()->insert()` throughout (no
 * Eloquent model instantiation) since this is thousands of rows — chunked
 * inserts and ID ranges computed from AUTO_INCREMENT position, not model
 * events or per-row queries.
 *
 * Idempotency: guarded by a single marker organization name below, matching
 * the pattern the existing demo seeders already use.
 */
class NationwideDemoSeeder extends Seeder
{
    private const MARKER_ORGANIZATION = 'Nationwide Demo Seed';

    private const PARTICIPANTS_PER_REGION_MIN = 140;

    private const PARTICIPANTS_PER_REGION_MAX = 260;

    private const TRAINING_REQUESTS_TOTAL = 900;

    private const INSTRUCTORS_PER_REGION_MIN = 8;

    private const INSTRUCTORS_PER_REGION_MAX = 16;

    private const PARTICIPANT_TYPES = [
        'Academe', 'Artisanal Fisherfolk', 'Barangay', 'Children',
        'City Government', 'Cooperatives', 'CSOs/NGOs',
        'Farmers and Landless Rural Workers', 'GOCC', 'Humanitarian',
        'Indigenous Peoples', 'Informal Sector', 'Local Chief Executive',
        'Municipal Government', 'National Government', 'OCD Personnel',
        'Others', 'Persons with Disabilities', 'Private Sector',
    ];

    private const ORG_TEMPLATES = [
        'City Government of :place', 'Municipality of :place', 'Barangay :place',
        ':place Provincial Government', ':place DRRM Office', ':place Red Cross Chapter',
        ':place State University', 'CSO Coalition for Disaster Resilience',
        ':place Farmers Cooperative', ':place Business Chamber',
        'Department of Social Welfare and Development', 'Philippine National Police - :region',
        ':place Youth Council',
    ];

    private const PLACE_NAMES = [
        'San Fernando', 'Guagua', 'Malolos', 'Cabanatuan', 'Olongapo', 'Baguio', 'Tuguegarao',
        'Santiago', 'Legazpi', 'Naga', 'Iloilo', 'Bacolod', 'Cebu', 'Tacloban', 'Zamboanga',
        'Cagayan de Oro', 'Davao', 'General Santos', 'Butuan', 'Cotabato', 'Marawi', 'Puerto Princesa',
        'Antipolo', 'Batangas', 'Lucena', 'Calamba', 'San Isidro', 'Commonwealth', 'Taguig', 'Pasig',
    ];

    private const VENUES = [
        'Training Center', 'Municipal Gym', 'Provincial Capitol Hall', 'Covered Court',
        'City Hall Annex', 'DRRM Operations Center', 'Multi-Purpose Hall', 'Conference Center',
    ];

    public function run(): void
    {
        if (DB::table('users')->where('organization', self::MARKER_ORGANIZATION)->exists()) {
            $this->command?->warn('NationwideDemoSeeder already ran — skipping.');

            return;
        }

        $this->command?->info('Seeding nationwide demo data — this inserts several thousand rows, give it a moment...');

        DB::transaction(function () {
            $regions = config('regions.list');
            $catalog = collect(config('trainings.catalog'));
            $geo = config('regions.geo');
            $passwordHash = Hash::make('password');

            $participantIdsByRegion = $this->seedParticipants($regions, $passwordHash);
            $instructorIdsByRegion = $this->seedInstructors($regions);
            $trainingRequestIds = $this->seedTrainingRequests($regions, $catalog, $geo, $participantIdsByRegion, $instructorIdsByRegion);
            $this->seedEvaluations($trainingRequestIds);
            $this->seedCalendarEvents($regions);
            $this->seedTnaSubmissions($catalog, $participantIdsByRegion);
        });

        $this->command?->info('Nationwide demo data seeded.');
    }

    /**
     * @return array<string, list<int>> participant user IDs keyed by region
     */
    private function seedParticipants(array $regions, string $passwordHash): array
    {
        $startId = (int) (DB::table('users')->max('id') ?? 0);
        $nextId = $startId + 1;
        $idsByRegion = [];
        $rows = [];
        $usedEmails = [];

        foreach ($regions as $region) {
            $count = random_int(self::PARTICIPANTS_PER_REGION_MIN, self::PARTICIPANTS_PER_REGION_MAX);
            $idsByRegion[$region] = [];

            for ($i = 0; $i < $count; $i++) {
                $name = fake()->name();
                $slug = Str::slug($name).'-'.$nextId;
                $email = "{$slug}@nationwide.demo.ocd.local";
                $usedEmails[$email] = true;

                $rows[] = [
                    'name' => $name,
                    'age' => random_int(18, 64),
                    'sex' => fake()->randomElement(['Male', 'Female']),
                    'participant_type' => fake()->randomElement(self::PARTICIPANT_TYPES),
                    'organization' => ($i === 0 && $region === $regions[0])
                        ? self::MARKER_ORGANIZATION
                        : $this->fakeOrganization($region),
                    'agency' => "OCD Regional Office - {$region}",
                    'region' => $region,
                    'mobile_number' => '09'.random_int(100000000, 999999999),
                    'email' => $email,
                    'password' => $passwordHash,
                    'role' => 'participant',
                    'theme' => 'light',
                    'locale' => 'en',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $idsByRegion[$region][] = $nextId;
                $nextId++;
            }
        }

        collect($rows)->chunk(500)->each(fn ($chunk) => DB::table('users')->insert($chunk->all()));

        return $idsByRegion;
    }

    /**
     * @return array<string, list<int>> instructor IDs keyed by region
     */
    private function seedInstructors(array $regions): array
    {
        $startId = (int) (DB::table('instructors')->max('id') ?? 0);
        $nextId = $startId + 1;
        $idsByRegion = [];
        $rows = [];
        $trainingTypes = collect(config('trainings.catalog'))->pluck('title')->all();

        foreach ($regions as $region) {
            $count = random_int(self::INSTRUCTORS_PER_REGION_MIN, self::INSTRUCTORS_PER_REGION_MAX);
            $idsByRegion[$region] = [];

            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    'name' => fake()->name(),
                    'email' => 'instructor'.$nextId.'@nationwide.demo.ocd.local',
                    'phone' => '09'.random_int(100000000, 999999999),
                    'sex' => fake()->randomElement(['Male', 'Female']),
                    'position' => fake()->randomElement(['Training Officer', 'Fire Marshal', 'DRRM Specialist', 'Retired Officer', 'Faculty Member']),
                    'training_type' => fake()->randomElement($trainingTypes),
                    'certificate_code' => 'INS-'.strtoupper(Str::random(6)),
                    'deployment' => fake()->boolean(30) ? fake()->randomElement(['IMT', 'RDANA', 'PDNA', 'EOC']) : null,
                    'agency_organization' => $this->fakeOrganization($region),
                    'region' => $region,
                    'rating' => round(random_int(350, 500) / 100, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $idsByRegion[$region][] = $nextId;
                $nextId++;
            }
        }

        collect($rows)->chunk(500)->each(fn ($chunk) => DB::table('instructors')->insert($chunk->all()));

        return $idsByRegion;
    }

    /**
     * @return list<array{id: int, region: string, status: string}>
     */
    private function seedTrainingRequests(array $regions, $catalog, array $geo, array $participantIdsByRegion, array $instructorIdsByRegion): array
    {
        $startId = (int) (DB::table('training_requests')->max('id') ?? 0);
        $nextId = $startId + 1;

        // Weighted so the historical record reads like a real program: mostly
        // completed, a working pipeline behind it, only a few declined.
        $statusWeights = [
            TrainingRequest::STATUS_COMPLETED => 55,
            TrainingRequest::STATUS_APPROVED => 15,
            TrainingRequest::STATUS_UNDER_REVIEW => 10,
            TrainingRequest::STATUS_SUBMITTED => 10,
            TrainingRequest::STATUS_DECLINED => 10,
        ];
        $statusPool = [];
        foreach ($statusWeights as $status => $weight) {
            $statusPool = array_merge($statusPool, array_fill(0, $weight, $status));
        }

        $periodStart = Carbon::create(2024, 1, 1);
        $periodEnd = now();
        $totalDays = $periodStart->diffInDays($periodEnd);

        $rows = [];
        $meta = [];
        $submitterPool = array_merge(...array_values($participantIdsByRegion));

        for ($i = 0; $i < self::TRAINING_REQUESTS_TOTAL; $i++) {
            $region = fake()->randomElement($regions);
            $training = $catalog->random();
            $category = fake()->randomElement([TrainingRequest::CATEGORY_APB, TrainingRequest::CATEGORY_TA]);
            $agencyType = fake()->randomElement([TrainingRequest::AGENCY_TYPE_LGU, TrainingRequest::AGENCY_TYPE_NGA]);
            $date = $periodStart->copy()->addDays(random_int(0, max(1, $totalDays)));
            $status = $statusPool[array_rand($statusPool)];

            // A request dated in the future can't already be completed/declined.
            if ($date->isFuture() && in_array($status, [TrainingRequest::STATUS_COMPLETED, TrainingRequest::STATUS_DECLINED], true)) {
                $status = TrainingRequest::STATUS_APPROVED;
            }

            $numberOfParticipants = random_int(15, 45);
            $isCompleted = $status === TrainingRequest::STATUS_COMPLETED;

            $submitterId = $submitterPool[array_rand($submitterPool)];
            $organization = $this->fakeOrganization($region);
            [$lat, $lng] = $geo[$region] ?? [12.8797, 121.7740];

            $row = [
                'user_id' => $submitterId,
                'reference_number' => 'TR-'.$date->year.'-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT),
                'training_slug' => $training['slug'],
                'training_title' => $training['title'],
                'category' => $category,
                'requesting_agency' => $organization,
                'lgu' => Str::contains($organization, ['City', 'Municipality', 'Barangay']) ? $organization : null,
                'region' => $region,
                'agency_type' => $agencyType,
                'latitude' => $lat + (random_int(-30, 30) / 1000),
                'longitude' => $lng + (random_int(-30, 30) / 1000),
                'teams_organized' => $isCompleted ? random_int(0, 4) : 0,
                'graduates_male' => 0,
                'graduates_female' => 0,
                'graduates_age_18_30' => 0,
                'graduates_age_31_45' => 0,
                'graduates_age_46_59' => 0,
                'graduates_age_60_up' => 0,
                'contact_person' => fake()->name(),
                'contact_number' => '09'.random_int(100000000, 999999999),
                'contact_email' => Str::slug($organization).'@example.gov.ph',
                'number_of_participants' => $numberOfParticipants,
                'preferred_date' => $date->toDateString(),
                'venue' => fake()->randomElement(self::PLACE_NAMES).' '.fake()->randomElement(self::VENUES),
                'purpose' => 'Capacity building for '.$training['title'].' in support of local disaster preparedness.',
                'tna_completed' => fake()->boolean(70),
                'logistics_acknowledged' => true,
                'signature_name' => fake()->name(),
                'status' => $status,
                'certificate_code' => $isCompleted && fake()->boolean(60) ? 'OCD-CDTI-'.$date->year.'-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT) : null,
                'certificate_remarks' => $isCompleted && fake()->boolean(60) ? fake()->randomElement(['completion', 'participation']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $rows[] = $row;
            $meta[] = [
                'id' => $nextId,
                'region' => $region,
                'status' => $status,
                'is_completed' => $isCompleted,
                'participant_count' => min($numberOfParticipants, random_int(5, 30)),
            ];
            $nextId++;
        }

        collect($rows)->chunk(300)->each(fn ($chunk) => DB::table('training_requests')->insert($chunk->all()));

        $this->attachParticipantsAndInstructors($meta, $participantIdsByRegion, $instructorIdsByRegion);

        return $meta;
    }

    /**
     * Attaches real participants (pivot) to each request, derives the
     * graduate demographic columns from those same participants for
     * completed requests, and links 1-3 instructors per request — the
     * instructor_training_request pivot has never been populated before
     * this seeder.
     */
    private function attachParticipantsAndInstructors(array $meta, array $participantIdsByRegion, array $instructorIdsByRegion): void
    {
        $pivotRows = [];
        $instructorPivotRows = [];
        $updates = []; // trainingRequestId => [graduates_male, graduates_female, age buckets...]

        // Ages are needed to bucket graduates — fetch once per region rather
        // than per training request.
        $agesByUserId = DB::table('users')->whereIn('id', array_merge(...array_values($participantIdsByRegion)))
            ->pluck('age', 'id');
        $sexByUserId = DB::table('users')->whereIn('id', array_merge(...array_values($participantIdsByRegion)))
            ->pluck('sex', 'id');

        foreach ($meta as $row) {
            $pool = $participantIdsByRegion[$row['region']] ?? [];
            if (empty($pool)) {
                continue;
            }

            $count = min($row['participant_count'], count($pool));
            $selected = (array) array_rand(array_flip($pool), max(1, $count));

            $demographics = ['male' => 0, 'female' => 0, 'a1' => 0, 'a2' => 0, 'a3' => 0, 'a4' => 0];

            foreach ($selected as $userId) {
                $pivotRows[] = [
                    'training_request_id' => $row['id'],
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($row['is_completed']) {
                    if (($sexByUserId[$userId] ?? null) === 'Male') {
                        $demographics['male']++;
                    } else {
                        $demographics['female']++;
                    }

                    $age = $agesByUserId[$userId] ?? 30;
                    match (true) {
                        $age <= 30 => $demographics['a1']++,
                        $age <= 45 => $demographics['a2']++,
                        $age <= 59 => $demographics['a3']++,
                        default => $demographics['a4']++,
                    };
                }
            }

            if ($row['is_completed']) {
                $updates[$row['id']] = $demographics;
            }

            $instructorPool = $instructorIdsByRegion[$row['region']] ?? [];
            if (! empty($instructorPool) && in_array($row['status'], [TrainingRequest::STATUS_COMPLETED, TrainingRequest::STATUS_APPROVED], true)) {
                $instructorCount = min(random_int(1, 3), count($instructorPool));
                $selectedInstructors = (array) array_rand(array_flip($instructorPool), $instructorCount);
                foreach ($selectedInstructors as $instructorId) {
                    $instructorPivotRows[] = [
                        'instructor_id' => $instructorId,
                        'training_request_id' => $row['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        collect($pivotRows)->chunk(500)->each(fn ($chunk) => DB::table('training_request_user')->insert($chunk->all()));
        collect($instructorPivotRows)->chunk(500)->each(fn ($chunk) => DB::table('instructor_training_request')->insert($chunk->all()));

        foreach ($updates as $trainingRequestId => $d) {
            DB::table('training_requests')->where('id', $trainingRequestId)->update([
                'graduates_male' => $d['male'],
                'graduates_female' => $d['female'],
                'graduates_age_18_30' => $d['a1'],
                'graduates_age_31_45' => $d['a2'],
                'graduates_age_46_59' => $d['a3'],
                'graduates_age_60_up' => $d['a4'],
            ]);
        }
    }

    /**
     * Adds admin-entered TrainingEvaluation rows (with per-participant
     * participant_scores) and participant-submitted ParticipantEvaluation
     * rows for a meaningful subset of completed sessions, so the Tools tab's
     * L1/L2 sections have real data to show at scale.
     */
    private function seedEvaluations(array $meta): void
    {
        $completed = collect($meta)->filter(fn ($row) => $row['is_completed'])->values();
        $sample = $completed->random(min(300, $completed->count()));

        $trainingEvalRows = [];
        $participantEvalRows = [];

        $modules = ['M1 - Orientation', 'M2 - Core Concepts', 'M3 - Practicum'];

        foreach ($sample as $row) {
            $participantIds = DB::table('training_request_user')->where('training_request_id', $row['id'])->pluck('user_id')->all();
            if (empty($participantIds)) {
                continue;
            }

            $scored = collect($participantIds)->random(min(count($participantIds), random_int(3, 12)));

            $participantScores = $scored->map(fn ($userId) => [
                'user_id' => $userId,
                'pretest_score' => random_int(40, 75),
                'posttest_score' => random_int(70, 98),
            ])->values()->all();

            $moduleRatings = collect($modules)->map(fn ($module) => [
                'module' => $module,
                'module_rating' => random_int(3, 5),
                'trainer_rating' => random_int(3, 5),
            ])->all();

            $trainingEvalRows[] = [
                'training_request_id' => $row['id'],
                'module_ratings' => json_encode($moduleRatings),
                'participant_scores' => json_encode($participantScores),
                'pretest_score' => (int) round(collect($participantScores)->avg('pretest_score')),
                'posttest_score' => (int) round(collect($participantScores)->avg('posttest_score')),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $comments = ['Great session, very informative.', 'Could be more interactive.', 'Well organized and on time.', 'Learned a lot, thank you.', 'Venue was a bit cramped.', 'Excellent facilitator.'];

            foreach ($scored as $userId) {
                $participantModuleRatings = collect($modules)->random(random_int(1, 3))->map(fn ($module) => [
                    'module' => $module,
                    'module_rating' => random_int(3, 5),
                    'comment' => fake()->boolean(40) ? fake()->randomElement($comments) : null,
                ])->values()->all();

                $instructorIds = DB::table('instructor_training_request')->where('training_request_id', $row['id'])->pluck('instructor_id')->all();
                $instructorRatings = collect($instructorIds)->map(fn ($instructorId) => [
                    'instructor_id' => $instructorId,
                    'rating' => random_int(3, 5),
                    'comment' => fake()->boolean(30) ? fake()->randomElement($comments) : null,
                ])->values()->all();

                $participantEvalRows[] = [
                    'training_request_id' => $row['id'],
                    'user_id' => $userId,
                    'module_ratings' => json_encode($participantModuleRatings),
                    'instructor_ratings' => json_encode($instructorRatings),
                    'overall_comments' => fake()->boolean(25) ? fake()->randomElement($comments) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        collect($trainingEvalRows)->chunk(300)->each(fn ($chunk) => DB::table('training_evaluations')->insert($chunk->all()));
        collect($participantEvalRows)->chunk(500)->each(fn ($chunk) => DB::table('participant_evaluations')->insert($chunk->all()));
    }

    private function seedCalendarEvents(array $regions): void
    {
        $holidays = [
            ['title' => 'National Heroes Day', 'type' => 'holiday', 'region' => null],
            ['title' => 'Bonifacio Day', 'type' => 'holiday', 'region' => null],
            ['title' => 'Independence Day', 'type' => 'holiday', 'region' => null],
            ['title' => 'Araw ng Kagitingan', 'type' => 'holiday', 'region' => null],
            ['title' => 'All Saints\' Day', 'type' => 'holiday', 'region' => null],
        ];

        $rows = [];
        $periodStart = Carbon::create(2024, 1, 1);

        foreach ($holidays as $i => $holiday) {
            $rows[] = array_merge($holiday, [
                'date' => $periodStart->copy()->addMonths($i * 5)->toDateString(),
                'end_date' => null,
                'description' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // A handful of regional class/work suspensions, spread across regions
        // and the period, so the Calendar's month tabs aren't holiday-only.
        for ($i = 0; $i < 15; $i++) {
            $region = fake()->randomElement($regions);
            $date = $periodStart->copy()->addDays(random_int(0, 900));
            $rows[] = [
                'title' => 'Class/Work Suspension - '.$region,
                'type' => 'suspension',
                'date' => $date->toDateString(),
                'end_date' => fake()->boolean(30) ? $date->copy()->addDay()->toDateString() : null,
                'region' => $region,
                'description' => fake()->randomElement(['Severe weather advisory', 'Local flooding', 'Typhoon signal raised']),
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('calendar_events')->insert($rows);
    }

    private function seedTnaSubmissions($catalog, array $participantIdsByRegion): void
    {
        $allParticipantIds = array_merge(...array_values($participantIdsByRegion));
        $sample = collect($allParticipantIds)->random(min(500, count($allParticipantIds)));

        $categories = ['DRRM Core', 'Community Resilience', 'Assessment & Planning', 'Emergency Response', 'Planning', 'Preparedness'];
        $rows = [];

        foreach ($sample as $userId) {
            $training = $catalog->random();
            $topCategory = fake()->randomElement($categories);

            $rows[] = [
                'user_id' => $userId,
                'answers' => json_encode(['q1' => fake()->randomElement(['low', 'medium', 'high']), 'q2' => fake()->randomElement(['low', 'medium', 'high'])]),
                'category_scores' => json_encode(array_fill_keys($categories, random_int(1, 10))),
                'top_category' => $topCategory,
                'max_hours' => fake()->randomElement([4, 8, 12, 16, 24]),
                'recommended_training_slug' => $training['slug'],
                'recommended_training_title' => $training['title'],
                'recommended_training_category' => $training['category'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        collect($rows)->chunk(500)->each(fn ($chunk) => DB::table('training_needs_assessments')->insert($chunk->all()));
    }

    private function fakeOrganization(string $region): string
    {
        $template = fake()->randomElement(self::ORG_TEMPLATES);

        return str_replace(
            [':place', ':region'],
            [fake()->randomElement(self::PLACE_NAMES), $region],
            $template
        );
    }
}
