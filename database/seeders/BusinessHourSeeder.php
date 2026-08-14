<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    /**
     * Requirements.md §16. Carbon day-of-week: 0 = Sunday .. 6 = Saturday.
     *
     * Friday/Saturday close at midnight per the spec; stored as 23:59:59
     * since the schema doesn't yet model hours that cross into the next
     * calendar day. Revisit when the Availability Engine is built.
     */
    public function run(): void
    {
        $hours = [
            0 => ['06:00:00', '22:00:00'], // Sunday
            1 => ['06:00:00', '23:00:00'], // Monday
            2 => ['06:00:00', '23:00:00'], // Tuesday
            3 => ['06:00:00', '23:00:00'], // Wednesday
            4 => ['06:00:00', '23:00:00'], // Thursday
            5 => ['06:00:00', '23:59:59'], // Friday
            6 => ['06:00:00', '23:59:59'], // Saturday
        ];

        foreach ($hours as $day => [$opens, $closes]) {
            BusinessHour::create([
                'day_of_week' => $day,
                'opens_at' => $opens,
                'closes_at' => $closes,
                'is_closed' => false,
            ]);
        }
    }
}
