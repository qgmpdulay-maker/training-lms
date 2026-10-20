<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_one_ip_cannot_spray_a_password_across_many_accounts(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->post('/login', ['email' => "person{$i}@example.com", 'password' => 'Password1!'])
                ->assertStatus(302);
        }

        $this->post('/login', ['email' => 'one-more@example.com', 'password' => 'Password1!'])
            ->assertStatus(429);
    }
}
