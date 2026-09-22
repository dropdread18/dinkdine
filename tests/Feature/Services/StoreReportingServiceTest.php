<?php

namespace Tests\Feature\Services;

use App\Enums\StoreSalePaymentMethod;
use App\Models\Product;
use App\Models\StoreSale;
use App\Models\StoreSaleItem;
use App\Services\StoreReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StoreReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private StoreReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StoreReportingService;
    }

    public function test_daily_sales_totals_only_sales_within_the_range(): void
    {
        StoreSale::factory()->create(['total' => 100, 'created_at' => Carbon::parse('2026-06-15 10:00:00')]);
        StoreSale::factory()->create(['total' => 50, 'created_at' => Carbon::parse('2026-06-15 14:00:00')]);
        StoreSale::factory()->create(['total' => 999, 'created_at' => Carbon::parse('2026-06-20 10:00:00')]);

        $result = $this->service->dailySales(Carbon::parse('2026-06-15'), Carbon::parse('2026-06-15'));

        $this->assertSame(150.0, $result['total']);
        $this->assertSame(2, $result['count']);
    }

    public function test_sales_by_date_is_zero_filled_for_days_with_no_sales(): void
    {
        StoreSale::factory()->create(['total' => 100, 'created_at' => Carbon::parse('2026-06-15 10:00:00')]);

        $result = $this->service->salesByDate(Carbon::parse('2026-06-14'), Carbon::parse('2026-06-16'));

        $this->assertCount(3, $result);
        $this->assertSame(0.0, $result[0]['total']);
        $this->assertSame(100.0, $result[1]['total']);
        $this->assertSame(0.0, $result[2]['total']);
    }

    public function test_payment_breakdown_groups_by_method_and_zero_fills_unused_ones(): void
    {
        StoreSale::factory()->create(['payment_method' => 'cash', 'total' => 100, 'created_at' => Carbon::parse('2026-06-15')]);
        StoreSale::factory()->create(['payment_method' => 'cash', 'total' => 50, 'created_at' => Carbon::parse('2026-06-15')]);
        StoreSale::factory()->create(['payment_method' => 'gcash', 'total' => 200, 'created_at' => Carbon::parse('2026-06-15')]);

        $result = $this->service->paymentBreakdown(Carbon::parse('2026-06-15'), Carbon::parse('2026-06-15'))->keyBy(fn ($row) => $row['method']->value);

        $this->assertSame(150.0, $result['cash']['total']);
        $this->assertSame(2, $result['cash']['count']);
        $this->assertSame(200.0, $result['gcash']['total']);
        $this->assertSame(0.0, $result['card']['total']);
        $this->assertSame(0, $result['card']['count']);
    }

    public function test_product_sales_groups_by_the_snapshotted_product_name(): void
    {
        $saleA = StoreSale::factory()->create(['created_at' => Carbon::parse('2026-06-15')]);
        $saleB = StoreSale::factory()->create(['created_at' => Carbon::parse('2026-06-15')]);
        StoreSaleItem::factory()->create(['store_sale_id' => $saleA->id, 'product_name' => 'Cafe Cubito', 'quantity' => 2, 'subtotal' => 330]);
        StoreSaleItem::factory()->create(['store_sale_id' => $saleB->id, 'product_name' => 'Cafe Cubito', 'quantity' => 1, 'subtotal' => 165]);
        StoreSaleItem::factory()->create(['store_sale_id' => $saleB->id, 'product_name' => 'Bottled Water', 'quantity' => 3, 'subtotal' => 90]);

        $result = $this->service->productSales(Carbon::parse('2026-06-15'), Carbon::parse('2026-06-15'))->keyBy('product_name');

        $this->assertSame(3, (int) $result['Cafe Cubito']->quantity_sold);
        $this->assertSame(495.0, (float) $result['Cafe Cubito']->revenue);
        $this->assertSame(3, (int) $result['Bottled Water']->quantity_sold);
    }

    public function test_product_sales_excludes_items_outside_the_range(): void
    {
        $inRange = StoreSale::factory()->create(['created_at' => Carbon::parse('2026-06-15')]);
        $outOfRange = StoreSale::factory()->create(['created_at' => Carbon::parse('2026-01-01')]);
        StoreSaleItem::factory()->create(['store_sale_id' => $inRange->id, 'product_name' => 'Cafe Cubito']);
        StoreSaleItem::factory()->create(['store_sale_id' => $outOfRange->id, 'product_name' => 'Old Sale Item']);

        $result = $this->service->productSales(Carbon::parse('2026-06-15'), Carbon::parse('2026-06-15'));

        $this->assertTrue($result->contains('product_name', 'Cafe Cubito'));
        $this->assertFalse($result->contains('product_name', 'Old Sale Item'));
    }

    public function test_inventory_status_counts_ok_low_and_out_of_stock_active_products(): void
    {
        Product::factory()->create(['stock_quantity' => 50, 'minimum_stock' => 5]); // ok
        Product::factory()->lowStock()->create(); // low
        Product::factory()->outOfStock()->create(); // out
        Product::factory()->inactive()->create(['stock_quantity' => 0]); // excluded

        $result = $this->service->inventoryStatus();

        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['ok']);
        $this->assertSame(1, $result['low']);
        $this->assertSame(1, $result['out']);
    }
}
