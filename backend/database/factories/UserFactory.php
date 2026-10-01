<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Shared bcrypt hash so we only compute it once per test run. */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::User,
            'remember_token' => Str::random(10),
        ];
    }

    /** Produce an admin user. */
    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    /** Produce a regular user (explicit, mirrors admin() for symmetry). */
    public function user(): static
    {
        return $this->state(['role' => Role::User]);
    }

    /** Unverified email address variant. */
    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
