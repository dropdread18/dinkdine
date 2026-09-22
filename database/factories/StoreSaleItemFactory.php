<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StoreSale;
use App\Models\StoreSaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSaleItem>
 */
class StoreSaleItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_sale_id' => StoreSale::factory(),
            'product_id' => Product::factory(),
            'product_name' => 'Cafe Cubito',
            'quantity' => 1,
            'unit_price' => 165,
            'cost_price' => 75,
            'subtotal' => 165,
        ];
    }
}
