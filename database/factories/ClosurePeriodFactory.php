<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ClosurePeriod>
 */
class ClosurePeriodFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 60))->setTime(0, 0);

        return [
            'title' => fake()->randomElement(['Facility Closed', 'Private Event', 'Holiday']),
            'starts_at' => $start,
            'ends_at' => $start->copy()->endOfDay(),
            'notes' => null,
        ];
    }
}
