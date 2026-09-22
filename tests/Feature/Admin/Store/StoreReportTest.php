<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Product;
use App\Models\StoreSale;
use App\Models\StoreSaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StoreReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_store_reports(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/reports')
            ->assertOk()
            ->assertSee('Store Reports');
    }

    public function test_reports_default_to_todays_sales(): void
    {
        $sale = StoreSale::factory()->create(['total' => 250, 'created_at' => Carbon::today()->addHours(2)]);
        StoreSale::factory()->create(['total' => 999, 'created_at' => Carbon::today()->subDays(10)]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/reports');

        $response->assertOk()->assertSee('250.00')->assertDontSee('999.00');
    }

    public function test_week_range_includes_a_sale_from_earlier_this_week(): void
    {
        $thisWeek = Carbon::today()->startOfWeek()->addDay();
        StoreSale::factory()->create(['total' => 321, 'created_at' => $thisWeek]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/reports?range=week');

        $response->assertOk()->assertSee('321.00');
    }

    public function test_custom_range_filters_to_the_given_dates(): void
    {
        StoreSale::factory()->create(['total' => 111, 'created_at' => Carbon::parse('2026-06-15')]);
        StoreSale::factory()->create(['total' => 222, 'created_at' => Carbon::parse('2026-07-15')]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/reports?'.http_build_query(['range' => 'custom', 'start' => '2026-06-01', 'end' => '2026-06-30']));

        $response->assertOk()->assertSee('111.00')->assertDontSee('222.00');
    }

    public function test_product_sales_table_shows_the_products_sold(): void
    {
        $sale = StoreSale::factory()->create(['created_at' => Carbon::today()]);
        StoreSaleItem::factory()->create(['store_sale_id' => $sale->id, 'product_name' => 'Cafe Cubito', 'quantity' => 5]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/reports');

        $response->assertOk()->assertSee('Cafe Cubito');
    }

    public function test_inventory_status_reflects_current_stock(): void
    {
        Product::factory()->outOfStock()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/reports');

        $response->assertOk()->assertSee('Out of Stock');
    }

    public function test_staff_cannot_access_store_reports(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/store/reports')
            ->assertForbidden();
    }
}
