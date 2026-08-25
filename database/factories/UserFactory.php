<?php

namespace Database\Factories;

use App\Enums\UserRole;
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
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'password_set_at' => now(),
            'remember_token' => Str::random(10),
            'role' => UserRole::Customer,
        ];
    }

    /**
     * A guest-checkout or staff-created walk-in account - an unknowable
     * random password the person never actually chose, matching what
     * BookingGrid::resolveCustomer() and WalkInBookingController produce.
     */
    public function guestBooker(): static
    {
        return $this->state(fn (array $attributes) => ['password_set_at' => null]);
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

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Staff]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Customer]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
