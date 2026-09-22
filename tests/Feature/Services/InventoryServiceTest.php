<?php

namespace Tests\Feature\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryAdjustmentException;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InventoryService;
    }

    public function test_a_positive_movement_increases_stock_and_records_previous_and_new_quantities(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);
        $admin = User::factory()->admin()->create();

        $movement = $this->service->recordMovement($product, InventoryMovementType::StockIn, 20, $admin, 'New delivery');

        $this->assertSame(24, $movement->previous_quantity);
        $this->assertSame(44, $movement->new_quantity);
        $this->assertSame(20, $movement->quantity);
        $this->assertSame($admin->id, $movement->user_id);
        $this->assertSame(44, $product->fresh()->stock_quantity);
    }

    public function test_a_negative_movement_decreases_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);

        $movement = $this->service->recordMovement($product, InventoryMovementType::Damaged, -3, User::factory()->admin()->create());

        $this->assertSame(21, $movement->new_quantity);
        $this->assertSame(21, $product->fresh()->stock_quantity);
    }

    public function test_a_movement_that_would_go_negative_is_rejected_and_changes_nothing(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->expectException(InventoryAdjustmentException::class);

        try {
            $this->service->recordMovement($product, InventoryMovementType::Lost, -10, User::factory()->admin()->create());
        } finally {
            $this->assertSame(5, $product->fresh()->stock_quantity);
            $this->assertSame(0, \App\Models\InventoryMovement::count());
        }
    }
}
