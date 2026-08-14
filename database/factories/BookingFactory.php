<?php

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(8, 20);

        return [
            'user_id' => User::factory()->customer(),
            'court_id' => Court::factory(),
            'booking_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'start_time' => sprintf('%02d:00:00', $start),
            'end_time' => sprintf('%02d:00:00', $start + 1),
            'price' => 300,
            'status' => BookingStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'source' => BookingSource::Online,
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::Pending]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::Cancelled]);
    }

    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => ['source' => BookingSource::WalkIn]);
    }
}
