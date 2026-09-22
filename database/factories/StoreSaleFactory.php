<?php

namespace Database\Factories;

use App\Enums\StoreSalePaymentMethod;
use App\Models\StoreSale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSale>
 */
class StoreSaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_number' => 'SALE-'.fake()->unique()->numberBetween(1, 999999),
            'subtotal' => 165,
            'discount' => 0,
            'total' => 165,
            'payment_method' => StoreSalePaymentMethod::Cash,
            'amount_paid' => 165,
            'change_amount' => 0,
            'user_id' => User::factory()->admin(),
        ];
    }
}
