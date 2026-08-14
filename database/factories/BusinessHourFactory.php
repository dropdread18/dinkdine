<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BusinessHour>
 */
class BusinessHourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->unique()->numberBetween(0, 6),
            'opens_at' => '06:00:00',
            'closes_at' => '22:00:00',
            'is_closed' => false,
        ];
    }
}
