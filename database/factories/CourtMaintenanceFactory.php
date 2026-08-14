<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CourtMaintenance>
 */
class CourtMaintenanceFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 30))->setTime(fake()->numberBetween(8, 18), 0);

        return [
            'court_id' => Court::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'reason' => 'Maintenance',
        ];
    }
}
