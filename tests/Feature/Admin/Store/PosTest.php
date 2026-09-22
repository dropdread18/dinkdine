<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Product;
use App\Models\StoreSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_pos_product_grid(): void
    {
        $product = Product::factory()->create(['name' => 'Cafe Cubito']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/pos')
            ->assertOk()
            ->assertSee('Cafe Cubito');
    }

    public function test_inactive_products_do_not_appear_on_the_pos_grid(): void
    {
        $active = Product::factory()->create(['name' => 'Active Product']);
        $inactive = Product::factory()->inactive()->create(['name' => 'Inactive Product']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/pos');

        $response->assertSee('Active Product');
        $response->assertDontSee('Inactive Product');
    }

    public function test_pos_grid_exposes_each_products_barcode_for_the_camera_scanner(): void
    {
        $product = Product::factory()->create(['name' => 'Cafe Cubito', 'barcode' => '4800016641503']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/pos');

        $response->assertOk();
        $response->assertSee('data-barcode="4800016641503"', false);
    }

    public function test_review_page_shows_selected_items_and_subtotal(): void
    {
        $product = Product::factory()->create(['name' => 'Cafe Cubito', 'selling_price' => 165]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/pos/review?'.http_build_query(['quantities' => [$product->id => 2]]));

        $response->assertOk()
            ->assertSee('Cafe Cubito')
            ->assertSee('330.00');
    }

    public function test_selecting_nothing_redirects_back_to_the_grid_with_an_error(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/pos/review?'.http_build_query(['quantities' => []]));

        $response->assertRedirect(route('admin.store.pos.index'));
        $response->assertSessionHasErrors('cart');
    }

    public function test_admin_can_complete_a_sale(): void
    {
        $product = Product::factory()->create(['selling_price' => 165, 'stock_quantity' => 24]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/store/pos/checkout', [
            'quantities' => [$product->id => 2],
            'payment_method' => 'cash',
            'amount_paid' => 400,
        ]);

        $sale = StoreSale::first();
        $response->assertRedirect(route('admin.store.sales.show', $sale));
        $this->assertSame(22, $product->fresh()->stock_quantity);
        $this->assertSame(70.0, (float) $sale->change_amount);
    }

    public function test_checkout_fails_gracefully_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 1]);

        $response = $this->actingAs(User::factory()->admin()->create())->post('/admin/store/pos/checkout', [
            'quantities' => [$product->id => 5],
            'payment_method' => 'cash',
            'amount_paid' => 1000,
        ]);

        $response->assertSessionHasErrors('cart');
        $this->assertSame(0, StoreSale::count());
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_staff_can_view_the_pos_grid(): void
    {
        $product = Product::factory()->create(['name' => 'Cafe Cubito']);

        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/store/pos')
            ->assertOk()
            ->assertSee('Cafe Cubito');
    }

    public function test_staff_can_complete_a_sale(): void
    {
        $product = Product::factory()->create(['selling_price' => 165, 'stock_quantity' => 24]);
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->post('/admin/store/pos/checkout', [
            'quantities' => [$product->id => 1],
            'payment_method' => 'cash',
            'amount_paid' => 200,
        ]);

        $sale = StoreSale::first();
        $response->assertRedirect(route('admin.store.sales.show', $sale));
        $this->assertSame($staff->id, $sale->user_id);
        $this->assertSame(23, $product->fresh()->stock_quantity);
    }
}
