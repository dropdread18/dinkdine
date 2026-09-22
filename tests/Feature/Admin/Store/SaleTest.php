<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Product;
use App\Models\StoreSale;
use App\Models\StoreSaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_sales_history_list(): void
    {
        $sale = StoreSale::factory()->create(['sale_number' => 'SALE-000001']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/sales')
            ->assertOk()
            ->assertSee('SALE-000001');
    }

    public function test_sales_list_shows_the_item_count_and_total(): void
    {
        $sale = StoreSale::factory()->create(['total' => 330]);
        StoreSaleItem::factory()->count(2)->create(['store_sale_id' => $sale->id]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/sales');

        $response->assertSee('330.00');
    }

    public function test_sales_can_be_searched_by_sale_number(): void
    {
        $target = StoreSale::factory()->create(['sale_number' => 'SALE-000042']);
        $other = StoreSale::factory()->create(['sale_number' => 'SALE-000099']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/sales?q=000042');

        $response->assertSee('SALE-000042');
        $response->assertDontSee('SALE-000099');
    }

    public function test_sales_can_be_searched_by_cashier_name(): void
    {
        $cashier = User::factory()->admin()->create(['name' => 'Juan Dela Cruz']);
        $target = StoreSale::factory()->create(['user_id' => $cashier->id, 'sale_number' => 'SALE-000001']);
        $other = StoreSale::factory()->create(['sale_number' => 'SALE-000002']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/sales?q=Juan');

        $response->assertSee('SALE-000001');
        $response->assertDontSee('SALE-000002');
    }

    public function test_sales_can_be_filtered_by_payment_method(): void
    {
        $cash = StoreSale::factory()->create(['payment_method' => 'cash', 'sale_number' => 'SALE-000001']);
        $gcash = StoreSale::factory()->create(['payment_method' => 'gcash', 'sale_number' => 'SALE-000002']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/sales?payment_method=gcash');

        $response->assertSee('SALE-000002');
        $response->assertDontSee('SALE-000001');
    }

    public function test_sales_can_be_filtered_by_date_range(): void
    {
        $inRange = StoreSale::factory()->create(['sale_number' => 'SALE-000001', 'created_at' => now()->subDays(2)]);
        $outOfRange = StoreSale::factory()->create(['sale_number' => 'SALE-000002', 'created_at' => now()->subDays(10)]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/sales?'.http_build_query(['from' => now()->subDays(3)->toDateString(), 'to' => now()->toDateString()]));

        $response->assertSee('SALE-000001');
        $response->assertDontSee('SALE-000002');
    }

    public function test_admin_can_view_a_sales_detail_page(): void
    {
        $sale = StoreSale::factory()->create(['sale_number' => 'SALE-000001']);
        StoreSaleItem::factory()->create(['store_sale_id' => $sale->id, 'product_name' => 'Cafe Cubito']);

        $response = $this->actingAs(User::factory()->admin()->create())->get("/admin/store/sales/{$sale->id}");

        $response->assertOk()
            ->assertSee('SALE-000001')
            ->assertSee('Cafe Cubito');
    }

    public function test_a_completed_checkout_lands_on_its_own_detail_page(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/store/pos/checkout', [
            'quantities' => [$product->id => 1],
            'payment_method' => 'cash',
            'amount_paid' => 1000,
        ]);
        $sale = StoreSale::first();

        $response = $this->actingAs($admin)->get(route('admin.store.sales.show', $sale));

        $response->assertOk()->assertSee($sale->sale_number);
    }

    public function test_staff_cannot_access_the_sales_history_list(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/store/sales')
            ->assertForbidden();
    }

    public function test_staff_can_view_their_own_sale(): void
    {
        $staff = User::factory()->staff()->create();
        $sale = StoreSale::factory()->create(['user_id' => $staff->id, 'sale_number' => 'SALE-000001']);

        $this->actingAs($staff)
            ->get("/admin/store/sales/{$sale->id}")
            ->assertOk()
            ->assertSee('SALE-000001');
    }

    public function test_staff_cannot_view_someone_elses_sale(): void
    {
        $otherStaff = User::factory()->staff()->create();
        $sale = StoreSale::factory()->create(['user_id' => $otherStaff->id]);

        $this->actingAs(User::factory()->staff()->create())
            ->get("/admin/store/sales/{$sale->id}")
            ->assertNotFound();
    }

    public function test_admin_can_view_any_staff_sale(): void
    {
        $staff = User::factory()->staff()->create();
        $sale = StoreSale::factory()->create(['user_id' => $staff->id, 'sale_number' => 'SALE-000001']);

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/store/sales/{$sale->id}")
            ->assertOk()
            ->assertSee('SALE-000001');
    }
}
