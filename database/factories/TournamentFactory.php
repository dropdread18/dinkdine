<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Tournament>
 */
class TournamentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'tournament_name' => fake()->words(3, true).' Cup',
            'created_by' => null,
            'session_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'notes' => null,
            'reclub_link' => null,
        ];
    }
}
