<?php

namespace Tests\Feature\Services;

use App\Enums\InventoryMovementType;
use App\Enums\StoreSalePaymentMethod;
use App\Exceptions\StoreSaleException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StoreSale;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\StoreSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private StoreSaleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StoreSaleService(new InventoryService);
    }

    public function test_checkout_creates_a_sale_with_items_and_deducts_stock(): void
    {
        $product = Product::factory()->create(['selling_price' => 165, 'cost_price' => 75, 'stock_quantity' => 24, 'name' => 'Cafe Cubito']);
        $cashier = User::factory()->admin()->create();

        $sale = $this->service->checkout(
            cashier: $cashier,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: StoreSalePaymentMethod::Cash,
            amountPaid: 400,
        );

        $this->assertSame(330.0, (float) $sale->subtotal);
        $this->assertSame(330.0, (float) $sale->total);
        $this->assertSame(70.0, (float) $sale->change_amount);
        $this->assertSame($cashier->id, $sale->user_id);
        $this->assertMatchesRegularExpression('/^SALE-\d{6}$/', $sale->sale_number);

        $item = $sale->items()->first();
        $this->assertSame('Cafe Cubito', $item->product_name);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(165.0, (float) $item->unit_price);
        $this->assertSame(330.0, (float) $item->subtotal);

        $this->assertSame(22, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovementType::Sale->value,
            'quantity' => -2,
            'reference_type' => StoreSale::class,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_sale_number_is_derived_from_the_row_id_and_always_unique(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $cashier = User::factory()->admin()->create();

        $first = $this->service->checkout($cashier, [['product_id' => $product->id, 'quantity' => 1]], StoreSalePaymentMethod::Cash, 1000);
        $second = $this->service->checkout($cashier, [['product_id' => $product->id, 'quantity' => 1]], StoreSalePaymentMethod::Cash, 1000);

        $this->assertNotSame($first->sale_number, $second->sale_number);
        $this->assertSame(sprintf('SALE-%06d', $first->id), $first->sale_number);
        $this->assertSame(sprintf('SALE-%06d', $second->id), $second->sale_number);
    }

    public function test_discount_reduces_the_total_but_not_the_subtotal(): void
    {
        $product = Product::factory()->create(['selling_price' => 100, 'stock_quantity' => 10]);

        $sale = $this->service->checkout(
            cashier: User::factory()->admin()->create(),
            items: [['product_id' => $product->id, 'quantity' => 1]],
            paymentMethod: StoreSalePaymentMethod::Cash,
            amountPaid: 100,
            discount: 20,
        );

        $this->assertSame(100.0, (float) $sale->subtotal);
        $this->assertSame(20.0, (float) $sale->discount);
        $this->assertSame(80.0, (float) $sale->total);
        $this->assertSame(20.0, (float) $sale->change_amount);
    }

    public function test_cannot_sell_more_than_available_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3]);

        $this->expectException(StoreSaleException::class);
        $this->expectExceptionMessage('Only 3');

        try {
            $this->service->checkout(User::factory()->admin()->create(), [['product_id' => $product->id, 'quantity' => 5]], StoreSalePaymentMethod::Cash, 1000);
        } finally {
            $this->assertSame(3, $product->fresh()->stock_quantity);
            $this->assertSame(0, StoreSale::count());
            $this->assertSame(0, InventoryMovement::count());
        }
    }

    public function test_amount_paid_below_the_total_is_rejected(): void
    {
        $product = Product::factory()->create(['selling_price' => 100, 'stock_quantity' => 10]);

        $this->expectException(StoreSaleException::class);

        try {
            $this->service->checkout(User::factory()->admin()->create(), [['product_id' => $product->id, 'quantity' => 1]], StoreSalePaymentMethod::Cash, 50);
        } finally {
            $this->assertSame(0, StoreSale::count());
        }
    }

    public function test_an_empty_cart_is_rejected(): void
    {
        $this->expectException(StoreSaleException::class);

        $this->service->checkout(User::factory()->admin()->create(), [], StoreSalePaymentMethod::Cash, 100);
    }

    public function test_an_inactive_product_cannot_be_sold(): void
    {
        $product = Product::factory()->inactive()->create(['stock_quantity' => 10]);

        $this->expectException(StoreSaleException::class);

        try {
            $this->service->checkout(User::factory()->admin()->create(), [['product_id' => $product->id, 'quantity' => 1]], StoreSalePaymentMethod::Cash, 1000);
        } finally {
            $this->assertSame(0, StoreSale::count());
        }
    }

    public function test_checkout_can_include_multiple_different_products(): void
    {
        $coffee = Product::factory()->create(['selling_price' => 165, 'stock_quantity' => 24]);
        $water = Product::factory()->create(['selling_price' => 30, 'stock_quantity' => 50]);

        $sale = $this->service->checkout(
            cashier: User::factory()->admin()->create(),
            items: [
                ['product_id' => $coffee->id, 'quantity' => 2],
                ['product_id' => $water->id, 'quantity' => 3],
            ],
            paymentMethod: StoreSalePaymentMethod::GCash,
            amountPaid: 500,
        );

        $this->assertSame(2, $sale->items()->count());
        $this->assertSame(420.0, (float) $sale->subtotal); // (165*2) + (30*3)
        $this->assertSame(22, $coffee->fresh()->stock_quantity);
        $this->assertSame(47, $water->fresh()->stock_quantity);
    }
}
