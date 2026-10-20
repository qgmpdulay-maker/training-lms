<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Participants can edit only their name, age, sex, and picture. Email,
 * participant type, organization, agency, and region are locked on the
 * profile form (see ProfileUpdateRequest), and there's no self-service
 * account deletion.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'age' => 34,
                'sex' => 'Female',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertEquals(34, $user->age);
        $this->assertSame('Female', $user->sex);
    }

    public function test_name_age_and_sex_are_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', []);

        $response
            ->assertSessionHasErrors(['name', 'age', 'sex'])
            ->assertRedirect('/profile');
    }

    public function test_locked_fields_are_ignored_when_submitted(): void
    {
        $user = User::factory()->create();
        $locked = ['email', 'participant_type', 'organization', 'agency', 'region', 'role'];
        $before = $user->fresh()->only($locked);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'age' => $user->age,
                'sex' => $user->sex,
                'email' => 'someone-else@example.com',
                'participant_type' => 'OCD Personnel',
                'organization' => 'Some Other Organization',
                'agency' => 'Some Other Agency',
                'region' => $user->region === 'NCR' ? 'CAR' : 'NCR',
                'role' => User::ROLE_SUPER_ADMIN,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame($before, $user->only($locked));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_the_id_card_carries_a_qr_code_of_the_id_number_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.id-card'))
            ->assertOk()
            ->assertSee(sprintf('OCD-PID-%06d', $user->id))
            ->assertSee('data:image/svg+xml;base64', false)
            ->assertSee(sprintf('QR code for OCD-PID-%06d', $user->id));
    }
}
