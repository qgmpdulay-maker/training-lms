<?php

namespace Tests\Feature\Auth;

use App\Mail\AccountPendingApproval;
use App\Mail\OtpCode;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Registering never creates an account or signs anyone in. It parks the
 * details in pending_registrations and emails a 6-digit code; once the code
 * is verified the registration waits for Super Admin approval, and only that
 * approval creates the real `users` row (PendingRegistration::approve()).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registering_creates_a_pending_registration_and_emails_a_code(): void
    {
        Mail::fake();

        $response = $this->post('/register', $this->registrationData());

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('otp.show'));

        $registration = PendingRegistration::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(PendingRegistration::STATUS_PENDING, $registration->status);
        $this->assertNull($registration->email_verified_at);
        $this->assertSame('Region III', $registration->region);
        $response->assertSessionHas('pending_registration_id', $registration->id);

        Mail::assertSent(OtpCode::class, fn (OtpCode $mail) => $mail->hasTo('test@example.com'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_verifying_the_code_sends_the_registration_to_super_admin_approval(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->superAdmin()->create();

        $this->post('/register', $this->registrationData());

        $response = $this->post('/verify-otp', ['code' => $this->sentCode()]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('registration.pending'));

        $this->assertNotNull(PendingRegistration::where('email', 'test@example.com')->value('email_verified_at'));

        Mail::assertSent(AccountPendingApproval::class, fn (AccountPendingApproval $mail) => $mail->hasTo($superAdmin->email));

        // Still no account until the Super Admin approves it.
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_a_wrong_code_does_not_verify_the_registration(): void
    {
        Mail::fake();

        $this->post('/register', $this->registrationData());

        // Codes are always 100000–999999, so this can never be the real one.
        $response = $this
            ->from(route('otp.show'))
            ->post('/verify-otp', ['code' => '000000']);

        $response
            ->assertSessionHasErrors('code')
            ->assertRedirect(route('otp.show'));

        $this->assertNull(PendingRegistration::where('email', 'test@example.com')->value('email_verified_at'));
        Mail::assertNotSent(AccountPendingApproval::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationData(): array
    {
        return [
            'name' => 'Test User',
            'age' => 30,
            'sex' => 'Female',
            'participant_type' => 'LGU Personnel',
            'city' => 'San Fernando',
            'region' => 'Region III',
            'email' => 'test@example.com',
            'password' => 'Sup3r-Secret!',
            'password_confirmation' => 'Sup3r-Secret!',
        ];
    }

    /**
     * The plain code only exists in the email (the database stores it hashed).
     */
    private function sentCode(): string
    {
        $code = null;

        Mail::assertSent(OtpCode::class, function (OtpCode $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }
}
