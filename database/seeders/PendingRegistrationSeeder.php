<?php

namespace Database\Seeders;

use App\Models\PendingRegistration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PendingRegistrationSeeder extends Seeder
{
    /**
     * Seeds a few sample account registrations sitting in different stages
     * of the OTP + Super Admin approval flow, so the process can be tried
     * out from the Manage Admins screen without registering fresh accounts
     * by hand. Every seeded password is "Password123!".
     */
    public function run(): void
    {
        // Already OTP-verified and sitting in the Super Admin's approval
        // queue — ready to Approve or Reject right away.
        $readyForDecision = [
            [
                'name' => 'Jasmine Rivera',
                'age' => 27,
                'sex' => 'Female',
                'participant_type' => 'CSOs/NGOs',
                'agency' => null,
                'city' => 'Cebu City',
                'email' => 'jasmine.rivera@example.com',
            ],
            [
                'name' => 'Marco Villanueva',
                'age' => 34,
                'sex' => 'Male',
                'participant_type' => 'Private Sector',
                'agency' => null,
                'city' => 'Davao City',
                'email' => 'marco.villanueva@example.com',
            ],
            [
                'name' => 'Corazon Bautista',
                'age' => 41,
                'sex' => 'Female',
                'participant_type' => 'Barangay',
                'agency' => null,
                'city' => 'Iloilo City',
                'email' => 'corazon.bautista@example.com',
            ],
        ];

        foreach ($readyForDecision as $data) {
            PendingRegistration::updateOrCreate(
                ['email' => $data['email']],
                $data + ['password' => Hash::make('Password123!')]
            )->forceFill([
                'email_verified_at' => now(),
                'status' => PendingRegistration::STATUS_PENDING,
                'otp_code' => null,
                'otp_expires_at' => null,
                'approved_user_id' => null,
            ])->save();
        }

        // Registered but never verified their email — demonstrates that this
        // one does NOT show up in the approval queue yet. Log in as
        // liza.fernandez@example.com / Password123! to trigger a fresh OTP
        // (readable in storage/logs/laravel.log, same as a real registration)
        // and walk the verification step yourself.
        PendingRegistration::updateOrCreate(
            ['email' => 'liza.fernandez@example.com'],
            [
                'name' => 'Liza Fernandez',
                'age' => 24,
                'sex' => 'Female',
                'participant_type' => 'Academe',
                'agency' => null,
                'city' => 'Baguio City',
                'password' => Hash::make('Password123!'),
            ]
        )->forceFill([
            'email_verified_at' => null,
            'status' => PendingRegistration::STATUS_PENDING,
            'otp_code' => null,
            'otp_expires_at' => null,
            'approved_user_id' => null,
        ])->save();
    }
}
