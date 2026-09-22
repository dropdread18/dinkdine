<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 999999),
            'description' => null,
            'cost_price' => 75,
            'selling_price' => 165,
            'unit' => 'piece',
            'stock_quantity' => 20,
            'minimum_stock' => 5,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock_quantity' => 3, 'minimum_stock' => 5]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock_quantity' => 0]);
    }
}
