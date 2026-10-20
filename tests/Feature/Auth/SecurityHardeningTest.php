<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_pages_tell_the_browser_not_to_keep_a_copy(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_after_logout_a_signed_in_page_sends_you_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->post('/logout');

        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_every_response_carries_the_framing_and_sniffing_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_a_password_reset_signs_that_account_out_of_sessions_already_open(): void
    {
        $participant = User::factory()->create();

        $this->post('/login', ['email' => $participant->email, 'password' => 'password']);
        $this->freshRequest()->get('/dashboard')->assertOk();

        // The Super Admin resets it from their own browser.
        $participant->forceFill(['password' => Hash::make('N3w-Passw0rd!')])->save();

        $this->freshRequest()->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_a_super_admin_resetting_their_own_password_stays_signed_in(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->forceFill(['role' => User::ROLE_SUPER_ADMIN])->save();

        $this->post('/login', ['email' => $superAdmin->email, 'password' => 'password']);
        $this->freshRequest()->get('/admin/users')->assertOk();

        $this->freshRequest()->from('/admin/users')
            ->post(route('admin.users.reset-password', $superAdmin))
            ->assertRedirect('/admin/users');

        $this->freshRequest()->get('/admin/users')->assertOk()->assertSee('password was reset');
    }

    public function test_one_ip_cannot_spray_a_password_across_many_accounts(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->post('/login', ['email' => "person{$i}@example.com", 'password' => 'Password1!'])
                ->assertStatus(302);
        }

        $this->post('/login', ['email' => 'one-more@example.com', 'password' => 'Password1!'])
            ->assertStatus(429);
    }

    /**
     * Within one test the auth guard keeps the user it loaded, so a password
     * changed in the database would go unseen. Forgetting the guard makes the
     * next request load the account from the session afresh, as a real one does.
     */
    private function freshRequest(): static
    {
        Auth::forgetGuards();

        return $this;
    }
}
