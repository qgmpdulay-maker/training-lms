<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'agency' => 'Office of Civil Defense',
            'mobile_number' => '09171234566',
            'age' => 30,
            'sex' => 'Male',
            'participant_type' => 'OCD PERSONNEL',
            'organization' => 'Office of Civil Defense',
        ]);

        tap(User::factory()->create([
            'name' => 'Regional Admin',
            'email' => 'admin@ocd.gov.ph',
            'agency' => 'OCD Regional Office III',
            'mobile_number' => '09171234567',
            'age' => 35,
            'sex' => 'Male',
            'participant_type' => 'OCD PERSONNEL',
            'organization' => 'Office of Civil Defense',
        ]))->forceFill([
            'role' => User::ROLE_ADMIN,
            'region' => 'Region III',
        ])->save();

        tap(User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@ocd.gov.ph',
            'agency' => 'CDTI',
            'mobile_number' => '09171234568',
            'age' => 40,
            'sex' => 'Female',
            'participant_type' => 'OCD PERSONNEL',
            'organization' => 'Civil Defense and Disaster Management Training Institute',
        ]))->forceFill([
            'role' => User::ROLE_SUPER_ADMIN,
        ])->save();
    }
}
