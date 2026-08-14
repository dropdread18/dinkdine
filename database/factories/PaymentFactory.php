<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => 300,
            'status' => PaymentStatus::Unpaid,
            'method' => null,
            'paid_at' => null,
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'method' => 'manual',
            'paid_at' => now(),
        ]);
    }
}
