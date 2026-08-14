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
}
