<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo accounts all share the known password "password" - fine for
        // local dev/testing, but must never exist on a real deployment. Guard
        // by environment rather than trusting whoever runs `db:seed` to
        // remember to skip this by hand.
        if (app()->environment('local', 'testing')) {
            User::factory()->admin()->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);

            User::factory()->staff()->create([
                'name' => 'Staff',
                'email' => 'staff@example.com',
            ]);

            User::factory()->customer()->create([
                'name' => 'Customer',
                'email' => 'customer@example.com',
            ]);
        }

        $this->call([
            CourtSeeder::class,
            BusinessHourSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
