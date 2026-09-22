<?php

namespace Database\Factories;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => InventoryMovementType::StockIn,
            'quantity' => 20,
            'previous_quantity' => 0,
            'new_quantity' => 20,
            'reason' => 'New delivery',
            'reference_type' => null,
            'reference_id' => null,
            'user_id' => User::factory()->admin(),
            'notes' => null,
        ];
    }
}
