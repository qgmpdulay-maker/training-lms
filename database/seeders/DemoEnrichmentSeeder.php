<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fills in the gaps DemoDataSeeder left behind so every admin-side feature has
 * something real to show: a second region (NCR) end to end, region/category
 * tags backfilled on the original untagged requests, fuller instructor
 * profiles, and a few more bulk (multi-participant) requests in Region III.
 */
class DemoEnrichmentSeeder extends Seeder
{
    private const NCR_PARTICIPANTS = [
        ['name' => 'Andrea Villareal', 'sex' => 'Female', 'age' => 29, 'type' => 'Barangay', 'org' => 'Barangay Commonwealth, Quezon City'],
        ['name' => 'Marco Aquino', 'sex' => 'Male', 'age' => 38, 'type' => 'City Government', 'org' => 'Manila DRRM Office'],
        ['name' => 'Bianca Ramirez', 'sex' => 'Female', 'age' => 33, 'type' => 'National Government', 'org' => 'Metropolitan Manila Development Authority'],
        ['name' => 'Joshua Tan', 'sex' => 'Male', 'age' => 26, 'type' => 'CSOs/NGOs', 'org' => 'Philippine Red Cross - Manila Chapter'],
        ['name' => 'Patricia Lim', 'sex' => 'Female', 'age' => 45, 'type' => 'City Government', 'org' => 'Makati City Government'],
        ['name' => 'Kevin Mercado', 'sex' => 'Male', 'age' => 31, 'type' => 'City Government', 'org' => 'Pasig City Government'],
        ['name' => 'Samantha Ocampo', 'sex' => 'Female', 'age' => 27, 'type' => 'Academe', 'org' => 'University of the Philippines Diliman'],
        ['name' => 'Rafael Domingo', 'sex' => 'Male', 'age' => 50, 'type' => 'National Government', 'org' => 'DILG-NCR'],
        ['name' => 'Nicole Fajardo', 'sex' => 'Female', 'age' => 24, 'type' => 'City Government', 'org' => 'Taguig DRRM Office'],
        ['name' => 'Christian Bautista', 'sex' => 'Male', 'age' => 42, 'type' => 'City Government', 'org' => 'Caloocan City Government'],
    ];

    private const NCR_INSTRUCTORS = [
        ['name' => 'Dir. Ramil Cortez', 'sex' => 'Male', 'position' => 'Regional Director', 'training' => 'Incident Command System (ICS)', 'rating' => 4.4, 'complaints' => 'A participant flagged that the March 2026 ICS session in Quezon City started 30 minutes late; addressed with a reminder on punctuality.'],
        ['name' => 'Dr. Leonora Pineda', 'sex' => 'Female', 'position' => 'Medical Officer III', 'training' => 'Psychological First Aid', 'rating' => 4.9, 'complaints' => null],
        ['name' => 'Capt. Noel Ibarra (Ret.)', 'sex' => 'Male', 'position' => 'Retired Officer', 'training' => 'Search and Rescue Operations', 'rating' => 4.7, 'complaints' => null],
        ['name' => 'Ms. Vivian Cruz', 'sex' => 'Female', 'position' => 'Training Specialist II', 'training' => 'Disaster Risk Reduction and Management Fundamentals', 'rating' => 4.6, 'complaints' => null],
        ['name' => 'Engr. Paolo Reyes', 'sex' => 'Male', 'position' => 'Engineer II', 'training' => 'Camp Coordination and Camp Management', 'rating' => null, 'complaints' => null],
    ];

    public function run(): void
    {
        if (User::where('email', 'like', '%@ncr.demo.ocd.local')->exists()) {
            $this->command?->info('Demo enrichment already seeded, skipping.');

            return;
        }

        $this->removeScratchTestData();
        $this->backfillLegacyRegionIIIRequests();
        $this->enrichRegionIIIInstructors();

        $region3Admin = User::where('email', 'admin@ocd.gov.ph')->first();
        if ($region3Admin) {
            $this->addRegionIIIBulkRequests($region3Admin);
        }

        $ncrAdmin = User::where('region', 'NCR')->where('role', User::ROLE_ADMIN)->first();
        $ncrParticipants = $this->createNcrParticipants();
        $this->createNcrInstructors();

        if ($ncrAdmin) {
            $this->createNcrTrainingRequests($ncrAdmin, $ncrParticipants);
        }

        $this->createNcrTna($ncrParticipants);

        $this->command?->info('Demo data enriched: NCR ecosystem, region tagging backfilled, instructor profiles filled in.');
    }

    private function removeScratchTestData(): void
    {
        TrainingRequest::where('requesting_agency', 'like', '%Test%')
            ->orWhere('requesting_agency', 'like', '%Dummy%')
            ->get()
            ->each(fn (TrainingRequest $r) => $r->delete());
    }

    /**
     * The original DemoDataSeeder participants are all Region III but never got
     * region/category tags (those fields didn't exist yet when it was written).
     * Tag most of them so Calendar/Tools/Instructors have real Region III data
     * to show; leave a handful untagged so the "needs tagging" story in Summary
     * still has something to demonstrate.
     */
    private function backfillLegacyRegionIIIRequests(): void
    {
        $untagged = TrainingRequest::whereNull('region')->inRandomOrder()->get()->values();
        $leaveUntagged = 8;
        $toTag = $untagged->slice(0, max(0, $untagged->count() - $leaveUntagged))->values();

        foreach ($toTag as $i => $request) {
            $request->region = 'Region III';
            $request->category = $i % 2 === 0 ? TrainingRequest::CATEGORY_APB : TrainingRequest::CATEGORY_TA;
            $request->save();
        }
    }

    private function enrichRegionIIIInstructors(): void
    {
        $updates = [
            'Engr. Ferdinand Lopez' => ['sex' => 'Male', 'position' => 'Fire Marshal', 'complaints' => 'A participant noted the June 2026 ICS session ran about 45 minutes over the scheduled end time; discussed with the instructor for future sessions.'],
            '2Lt. Bea Manalo (Ret.)' => ['sex' => 'Female', 'position' => 'Retired Officer'],
            'Dr. Samuel Ong' => ['sex' => 'Male', 'position' => 'Psychologist'],
            'Ana Bernardo' => ['sex' => 'Female', 'position' => 'Camp Manager'],
        ];

        foreach ($updates as $name => $fields) {
            Instructor::where('name', $name)->update($fields);
        }
    }

    /**
     * A few more bulk (multi-participant) Region III requests so Summary and
     * the Tools graduate counts have more than one multi-participant example.
     */
    private function addRegionIIIBulkRequests(User $admin): void
    {
        $participants = User::where('role', User::ROLE_PARTICIPANT)->where('region', 'Region III')->inRandomOrder()->get();

        if ($participants->count() < 6) {
            return;
        }

        $requests = [
            [
                'slug' => 'community-based-drr', 'title' => 'Community-Based Disaster Risk Reduction',
                'agency' => 'Municipality of Guagua', 'category' => TrainingRequest::CATEGORY_TA,
                'status' => TrainingRequest::STATUS_COMPLETED, 'days' => -40, 'venue' => 'Guagua Municipal Gym',
                'participants' => $participants->slice(0, 5), 'cert' => true,
            ],
            [
                'slug' => 'incident-command-system', 'title' => 'Incident Command System (ICS)',
                'agency' => 'City Government of San Fernando', 'category' => TrainingRequest::CATEGORY_APB,
                'status' => TrainingRequest::STATUS_APPROVED, 'days' => 25, 'venue' => 'City of San Fernando Training Center',
                'participants' => $participants->slice(5, 4), 'cert' => false,
            ],
        ];

        foreach ($requests as $data) {
            $this->createBulkRequest($admin, $data);
        }
    }

    private function createNcrParticipants(): \Illuminate\Support\Collection
    {
        $password = Hash::make('password');
        $created = collect();

        foreach (self::NCR_PARTICIPANTS as $person) {
            $slug = str($person['name'])->slug();
            $user = User::create([
                'name' => $person['name'],
                'email' => "{$slug}@ncr.demo.ocd.local",
                'password' => $password,
                'age' => $person['age'],
                'sex' => $person['sex'],
                'participant_type' => $person['type'],
                'organization' => $person['org'],
                'agency' => $person['org'],
                'region' => 'NCR',
                'mobile_number' => '09'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $created->push($user);
        }

        return $created;
    }

    private function createNcrInstructors(): void
    {
        foreach (self::NCR_INSTRUCTORS as $instructor) {
            Instructor::create([
                'name' => $instructor['name'],
                'sex' => $instructor['sex'],
                'position' => $instructor['position'],
                'training_type' => $instructor['training'],
                'certificate_code' => 'INS-'.strtoupper(str()->random(6)),
                'agency_organization' => 'OCD Civil Defense and Disaster Management Training Institute',
                'region' => 'NCR',
                'rating' => $instructor['rating'],
                'complaints' => $instructor['complaints'],
            ]);
        }
    }

    private function createNcrTrainingRequests(User $admin, \Illuminate\Support\Collection $participants): void
    {
        $requests = [
            [
                'slug' => 'search-and-rescue', 'title' => 'Search and Rescue Operations',
                'agency' => 'Manila DRRM Office', 'category' => TrainingRequest::CATEGORY_TA,
                'status' => TrainingRequest::STATUS_COMPLETED, 'days' => -70, 'venue' => 'Quezon City Sports Complex',
                'participants' => $participants->slice(0, 6), 'cert' => true,
            ],
            [
                'slug' => 'psychological-first-aid', 'title' => 'Psychological First Aid',
                'agency' => 'Manila City Government', 'category' => TrainingRequest::CATEGORY_APB,
                'status' => TrainingRequest::STATUS_COMPLETED, 'days' => -88, 'venue' => 'Manila City Hall Annex',
                'participants' => $participants->slice(0, 4), 'cert' => true,
            ],
            [
                'slug' => 'incident-command-system', 'title' => 'Incident Command System (ICS)',
                'agency' => 'University of the Philippines Diliman', 'category' => TrainingRequest::CATEGORY_TA,
                'status' => TrainingRequest::STATUS_COMPLETED, 'days' => -48, 'venue' => 'UP Diliman NISMED',
                'participants' => $participants->slice(4, 5), 'cert' => true,
            ],
            [
                'slug' => 'drrm-fundamentals', 'title' => 'Disaster Risk Reduction and Management Fundamentals',
                'agency' => 'Barangay Commonwealth, Quezon City', 'category' => TrainingRequest::CATEGORY_APB,
                'status' => TrainingRequest::STATUS_APPROVED, 'days' => 18, 'venue' => 'Barangay Commonwealth Covered Court',
                'participants' => $participants->slice(0, 5), 'cert' => false,
            ],
            [
                'slug' => 'incident-command-system', 'title' => 'Incident Command System (ICS)',
                'agency' => 'Metropolitan Manila Development Authority', 'category' => TrainingRequest::CATEGORY_TA,
                'status' => TrainingRequest::STATUS_APPROVED, 'days' => 33, 'venue' => 'MMDA Command Center',
                'participants' => $participants->slice(2, 3), 'cert' => false,
            ],
            [
                'slug' => 'camp-coordination-management', 'title' => 'Camp Coordination and Camp Management',
                'agency' => 'Pasig City Government', 'category' => TrainingRequest::CATEGORY_APB,
                'status' => TrainingRequest::STATUS_UNDER_REVIEW, 'days' => 40, 'venue' => 'Pasig City Coliseum',
                'participants' => $participants->slice(5, 4), 'cert' => false,
            ],
            [
                'slug' => 'community-based-drr', 'title' => 'Community-Based Disaster Risk Reduction',
                'agency' => 'Caloocan City Government', 'category' => TrainingRequest::CATEGORY_TA,
                'status' => TrainingRequest::STATUS_SUBMITTED, 'days' => 28, 'venue' => 'Caloocan City Gym',
                'participants' => $participants->slice(9, 1), 'cert' => false,
            ],
            [
                'slug' => 'drrm-fundamentals', 'title' => 'Disaster Risk Reduction and Management Fundamentals',
                'agency' => 'Makati City Government', 'category' => TrainingRequest::CATEGORY_APB,
                'status' => TrainingRequest::STATUS_SUBMITTED, 'days' => 45, 'venue' => 'Makati City Hall',
                'participants' => $participants->slice(4, 3), 'cert' => false,
            ],
            [
                'slug' => 'search-and-rescue', 'title' => 'Search and Rescue Operations',
                'agency' => 'Taguig DRRM Office', 'category' => TrainingRequest::CATEGORY_APB,
                'status' => TrainingRequest::STATUS_DECLINED, 'days' => 12, 'venue' => 'Taguig DRRM Training Hall',
                'participants' => $participants->slice(8, 1), 'cert' => false,
            ],
        ];

        $evaluationInstructors = [
            'Search and Rescue Operations' => 'Capt. Noel Ibarra (Ret.)',
            'Psychological First Aid' => 'Dr. Leonora Pineda',
            'Incident Command System (ICS)' => 'Dir. Ramil Cortez',
        ];

        foreach ($requests as $data) {
            $trainingRequest = $this->createBulkRequest($admin, $data);

            if ($data['status'] === TrainingRequest::STATUS_COMPLETED) {
                $this->addEvaluation($trainingRequest, $data['participants']->count());
            }
        }
    }

    private function createBulkRequest(User $admin, array $data): TrainingRequest
    {
        $trainingRequest = new TrainingRequest([
            'training_slug' => $data['slug'],
            'training_title' => $data['title'],
            'category' => $data['category'],
            'requesting_agency' => $data['agency'],
            'contact_person' => $data['participants']->first()?->name ?? $admin->name,
            'contact_number' => $data['participants']->first()?->mobile_number ?? '09171234567',
            'contact_email' => $data['participants']->first()?->email ?? $admin->email,
            'number_of_participants' => $data['participants']->count(),
            'preferred_date' => now()->addDays($data['days'])->toDateString(),
            'venue' => $data['venue'],
            'purpose' => 'Capacity building for local disaster risk reduction and management.',
            'tna_completed' => true,
            'logistics_acknowledged' => true,
            'signature_name' => $data['participants']->first()?->name ?? $admin->name,
        ]);

        $trainingRequest->user_id = $admin->id;
        $trainingRequest->region = $admin->region;
        $trainingRequest->status = $data['status'];

        if ($data['cert']) {
            $trainingRequest->lgu = $data['agency'];
            $trainingRequest->certificate_code = 'OCD-CDTI-2026-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $trainingRequest->certificate_remarks = TrainingRequest::CERTIFICATE_REMARKS_COMPLETION;
        }

        $trainingRequest->save();
        $trainingRequest->reference_number = sprintf('TR-%s-%05d', now()->year, $trainingRequest->id);
        $trainingRequest->save();

        $trainingRequest->participants()->sync($data['participants']->pluck('id'));

        return $trainingRequest;
    }

    private function addEvaluation(TrainingRequest $trainingRequest, int $participantCount): void
    {
        $moduleRatings = collect(['M1', 'M2', 'M3'])->map(fn ($module) => [
            'module' => $module,
            'module_rating' => random_int(4, 5),
            'trainer_rating' => random_int(4, 5),
            'comment' => null,
        ])->all();

        $trainingRequest->trainingEvaluation()->create([
            'module_ratings' => $moduleRatings,
            'pretest_score' => random_int(50, 70),
            'posttest_score' => random_int(80, 98),
        ]);
    }

    private function createNcrTna(\Illuminate\Support\Collection $participants): void
    {
        $catalog = collect(config('trainings.catalog'));
        $categories = ['Emergency Response', 'DRRM Core', 'Community Resilience', 'Health & Welfare'];

        foreach ($participants->take(5) as $participant) {
            $recommended = $catalog->random();
            $scores = collect($categories)->mapWithKeys(fn ($c) => [$c => random_int(2, 15)]);

            $participant->trainingNeedsAssessments()->create([
                'answers' => [
                    ['question' => 'What is your primary area of responsibility?', 'selected' => 'Community disaster preparedness', 'category' => 'Community Resilience', 'points' => 3, 'hours' => 4],
                    ['question' => 'Have you led an emergency response before?', 'selected' => 'No', 'category' => 'Emergency Response', 'points' => 2, 'hours' => 8],
                ],
                'category_scores' => $scores->all(),
                'top_category' => $scores->sortDesc()->keys()->first(),
                'max_hours' => $recommended['hours'],
                'recommended_training_slug' => $recommended['slug'],
                'recommended_training_title' => $recommended['title'],
                'recommended_training_category' => $recommended['category'],
            ]);
        }
    }
}
