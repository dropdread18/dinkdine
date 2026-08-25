<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OpenPlaySession>
 */
class OpenPlaySessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'created_by' => null,
            'session_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'notes' => 'Open Play',
        ];
    }
}
