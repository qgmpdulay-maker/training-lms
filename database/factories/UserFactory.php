<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            // age, sex, and participant_type are NOT NULL on the users table
            // (see 2026_08_04_055224_add_participant_fields_to_users_table.php),
            // so every factory-made user needs them.
            'age' => fake()->numberBetween(21, 60),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'participant_type' => 'LGU Personnel',
            'region' => fake()->randomElement(config('regions.list')),
            'role' => User::ROLE_PARTICIPANT,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * A Regional Admin for one OCD Regional Office.
     */
    public function admin(?string $region = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'region' => $region ?? $attributes['region'],
            'participant_type' => 'OCD Personnel',
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
            'participant_type' => 'OCD Personnel',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
