<?php

namespace Database\Factories;

use App\Enums\CourtStatus;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Court '.fake()->unique()->numberBetween(1, 100),
            'description' => null,
            'court_number' => fake()->unique()->numberBetween(1, 100),
            'hourly_rate' => fake()->randomElement([250, 300, 350, 400]),
            'status' => CourtStatus::Active,
            'court_type' => null,
            'location' => null,
            'image' => null,
            'sort_order' => 0,
        ];
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => ['status' => CourtStatus::Maintenance]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => CourtStatus::Closed]);
    }
}
