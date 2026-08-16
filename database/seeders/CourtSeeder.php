<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 4) as $number) {
            Court::create([
                'name' => "Court {$number}",
                'court_number' => $number,
                'hourly_rate' => 250,
                'evening_hourly_rate' => 350,
                'sort_order' => $number,
            ]);
        }
    }
}
