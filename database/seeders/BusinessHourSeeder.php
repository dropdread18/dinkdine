<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    /**
     * Requirements.md §16. Carbon day-of-week: 0 = Sunday .. 6 = Saturday.
     *
     * Owner request (2026-08-16): every day now opens 6:00 AM and closes
     * 2:00 AM the following morning, since players sometimes play past
     * midnight. closes_at < opens_at is AvailabilityService's convention
     * for "crosses into the next calendar day" (see its spilloverSlotTimes()).
     */
    public function run(): void
    {
        $hours = [
            0 => ['06:00:00', '02:00:00'], // Sunday
            1 => ['06:00:00', '02:00:00'], // Monday
            2 => ['06:00:00', '02:00:00'], // Tuesday
            3 => ['06:00:00', '02:00:00'], // Wednesday
            4 => ['06:00:00', '02:00:00'], // Thursday
            5 => ['06:00:00', '02:00:00'], // Friday
            6 => ['06:00:00', '02:00:00'], // Saturday
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
