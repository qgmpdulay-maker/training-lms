<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private const PARTICIPANT_TYPES = [
        'Academe', 'Artisanal Fisherfolk', 'Barangay', 'Children',
        'City Government', 'Cooperatives', 'CSOs/NGOs',
        'Farmers and Landless Rural Workers', 'GOCC', 'Humanitarian',
        'Indigenous Peoples', 'Informal Sector', 'Local Chief Executive',
        'Municipal Government', 'National Government', 'OCD Personnel',
        'Others', 'Persons with Disabilities', 'Private Sector',
    ];

    private const REGION_III_LGUS = [
        'City of San Fernando', 'Angeles City', 'Malolos City', 'Olongapo City',
        'Balanga City', 'Cabanatuan City', 'Tarlac City', 'Municipality of Guagua',
    ];

    private const PARTICIPANTS = [
        ['name' => 'Maria Santos', 'sex' => 'Female', 'age' => 34],
        ['name' => 'Juan Dela Cruz', 'sex' => 'Male', 'age' => 41],
        ['name' => 'Angelica Reyes', 'sex' => 'Female', 'age' => 28],
        ['name' => 'Ramon Bautista', 'sex' => 'Male', 'age' => 47],
        ['name' => 'Liza Mendoza', 'sex' => 'Female', 'age' => 31],
        ['name' => 'Carlos Villanueva', 'sex' => 'Male', 'age' => 39],
        ['name' => 'Grace Aquino', 'sex' => 'Female', 'age' => 26],
        ['name' => 'Eduardo Torres', 'sex' => 'Male', 'age' => 52],
        ['name' => 'Cristina Fernandez', 'sex' => 'Female', 'age' => 44],
        ['name' => 'Miguel Ramos', 'sex' => 'Male', 'age' => 29],
        ['name' => 'Rosario Garcia', 'sex' => 'Female', 'age' => 37],
        ['name' => 'Antonio Flores', 'sex' => 'Male', 'age' => 33],
        ['name' => 'Jasmine Cruz', 'sex' => 'Female', 'age' => 24],
        ['name' => 'Ricardo Navarro', 'sex' => 'Male', 'age' => 45],
        ['name' => 'Teresa Del Rosario', 'sex' => 'Female', 'age' => 50],
        ['name' => 'Paolo Salazar', 'sex' => 'Male', 'age' => 30],
        ['name' => 'Katrina Pascual', 'sex' => 'Female', 'age' => 27],
        ['name' => 'Benjamin Castillo', 'sex' => 'Male', 'age' => 43],
    ];

    private const ORGANIZATIONS = [
        'Barangay San Isidro', 'Municipality of Guagua', 'City Government of San Fernando',
        'Central Luzon State University', 'Pampanga Red Cross Chapter', 'Angeles City DRRM Office',
        'Nueva Ecija Farmers Cooperative', 'Bataan Provincial Government', 'Tarlac Youth Council',
        'Zambales Fisherfolk Association', 'Philippine National Police - Region III',
        'Bulacan State University', 'CSO Coalition for Disaster Resilience',
        'Department of Social Welfare and Development', 'Region III Business Chamber',
    ];

    private const CERTIFICATE_REMARKS = [
        TrainingRequest::CERTIFICATE_REMARKS_COMPLETION,
        TrainingRequest::CERTIFICATE_REMARKS_PARTICIPATION,
    ];

    public function run(): void
    {
        if (User::where('email', 'like', '%@demo.ocd.local')->exists()) {
            $this->command?->info('Demo data already seeded, skipping.');

            return;
        }

        $catalog = collect(config('trainings.catalog'));
        $statuses = [
            TrainingRequest::STATUS_SUBMITTED,
            TrainingRequest::STATUS_UNDER_REVIEW,
            TrainingRequest::STATUS_APPROVED,
            TrainingRequest::STATUS_DECLINED,
            TrainingRequest::STATUS_COMPLETED,
            TrainingRequest::STATUS_COMPLETED,
            TrainingRequest::STATUS_COMPLETED,
        ];

        $password = Hash::make('password');
        $certificateCodeSeq = 1;

        foreach (self::PARTICIPANTS as $i => $person) {
            $slug = str($person['name'])->slug();
            $user = User::create([
                'name' => $person['name'],
                'email' => "{$slug}@demo.ocd.local",
                'password' => $password,
                'age' => $person['age'],
                'sex' => $person['sex'],
                'participant_type' => self::PARTICIPANT_TYPES[$i % count(self::PARTICIPANT_TYPES)],
                'organization' => self::ORGANIZATIONS[$i % count(self::ORGANIZATIONS)],
                'agency' => self::ORGANIZATIONS[$i % count(self::ORGANIZATIONS)],
                'region' => 'Region III',
                'mobile_number' => '09'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $requestCount = random_int(1, 3);

            for ($r = 0; $r < $requestCount; $r++) {
                $training = $catalog->random();
                $status = $statuses[array_rand($statuses)];
                $daysOffset = random_int(-120, 45);

                $trainingRequest = $user->trainingRequests()->create([
                    'training_slug' => $training['slug'],
                    'training_title' => $training['title'],
                    'requesting_agency' => $user->organization,
                    'contact_person' => $user->name,
                    'contact_number' => $user->mobile_number,
                    'contact_email' => $user->email,
                    'number_of_participants' => random_int(1, 25),
                    'preferred_date' => now()->addDays($daysOffset)->toDateString(),
                    'venue' => self::REGION_III_LGUS[array_rand(self::REGION_III_LGUS)].' Training Center',
                    'purpose' => 'Capacity building for local disaster risk reduction and management.',
                    'signature_name' => $user->name,
                ]);

                // preferred_date in the future can't realistically be "completed" yet.
                if ($status === TrainingRequest::STATUS_COMPLETED && $daysOffset > 0) {
                    $status = TrainingRequest::STATUS_APPROVED;
                }

                $trainingRequest->status = $status;

                if ($status === TrainingRequest::STATUS_COMPLETED && random_int(1, 100) <= 65) {
                    $trainingRequest->lgu = self::REGION_III_LGUS[array_rand(self::REGION_III_LGUS)];
                    $trainingRequest->certificate_code = 'OCD-CDTI-2026-'.str_pad((string) $certificateCodeSeq++, 4, '0', STR_PAD_LEFT);
                    $trainingRequest->certificate_remarks = self::CERTIFICATE_REMARKS[array_rand(self::CERTIFICATE_REMARKS)];
                }

                $trainingRequest->save();
            }

            // About half the participants have taken the Training Needs Assessment.
            if ($i % 2 === 0) {
                $recommended = $catalog->random();
                $categories = ['Emergency Response', 'DRRM Core', 'Community Resilience', 'Health & Welfare'];
                $scores = collect($categories)->mapWithKeys(fn ($c) => [$c => random_int(2, 15)]);

                $user->trainingNeedsAssessments()->create([
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

        $instructors = [
            ['name' => 'Engr. Ferdinand Lopez', 'training_type' => 'Incident Command System (ICS)', 'region' => 'Region III', 'rating' => 4.6],
            ['name' => '2Lt. Bea Manalo (Ret.)', 'training_type' => 'Search and Rescue Operations', 'region' => 'Region III', 'rating' => 4.8],
            ['name' => 'Dr. Samuel Ong', 'training_type' => 'Psychological First Aid', 'region' => 'Region III', 'rating' => 4.9],
            ['name' => 'Prof. Corazon Sy', 'training_type' => 'Disaster Risk Reduction and Management Fundamentals', 'region' => 'NCR', 'rating' => 4.5],
            ['name' => 'Rene Dizon', 'training_type' => 'Community-Based Disaster Risk Reduction', 'region' => 'Region IV-A', 'rating' => 4.3],
            ['name' => 'Ana Bernardo', 'training_type' => 'Camp Coordination and Camp Management', 'region' => 'Region III', 'rating' => null],
        ];

        foreach ($instructors as $instructor) {
            Instructor::create([
                'name' => $instructor['name'],
                'training_type' => $instructor['training_type'],
                'certificate_code' => 'INS-'.strtoupper(str()->random(6)),
                'deployment' => null,
                'agency_organization' => 'OCD Civil Defense and Disaster Management Training Institute',
                'lgu' => null,
                'region' => $instructor['region'],
                'rating' => $instructor['rating'],
            ]);
        }

        $this->command?->info('Demo data seeded: '.count(self::PARTICIPANTS).' participants, instructors, and training requests.');
    }
}
