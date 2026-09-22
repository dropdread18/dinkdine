<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_products_list(): void
    {
        $product = Product::factory()->create(['name' => 'Cafe Cubito']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/products')
            ->assertOk()
            ->assertSee('Cafe Cubito');
    }

    public function test_admin_can_create_a_product(): void
    {
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/products', [
                'category_id' => $category->id,
                'name' => 'Cafe Cubito',
                'sku' => 'COF-001',
                'description' => null,
                'cost_price' => 75,
                'selling_price' => 165,
                'unit' => 'cup',
                'stock_quantity' => 24,
                'minimum_stock' => 5,
            ]);

        $response->assertRedirect(route('admin.store.products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Cafe Cubito',
            'sku' => 'COF-001',
            'category_id' => $category->id,
            'cost_price' => 75.00,
            'selling_price' => 165.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_a_product_with_a_barcode(): void
    {
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/products', [
                'category_id' => $category->id,
                'name' => 'Bottled Water',
                'sku' => 'WTR-001',
                'barcode' => '4800016641503',
                'cost_price' => 15,
                'selling_price' => 30,
                'unit' => 'bottle',
                'stock_quantity' => 50,
                'minimum_stock' => 10,
            ]);

        $response->assertRedirect(route('admin.store.products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Bottled Water', 'barcode' => '4800016641503']);
    }

    public function test_barcode_is_optional(): void
    {
        $category = ProductCategory::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/products', [
                'category_id' => $category->id,
                'name' => 'Cafe Cubito',
                'sku' => 'COF-001',
                'cost_price' => 75,
                'selling_price' => 165,
                'unit' => 'cup',
                'stock_quantity' => 24,
                'minimum_stock' => 5,
            ]);

        $response->assertRedirect(route('admin.store.products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Cafe Cubito', 'barcode' => null]);
    }

    public function test_barcode_must_be_unique(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['barcode' => '4800016641503']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/products', [
                'category_id' => $category->id,
                'name' => 'Duplicate Barcode',
                'sku' => 'DUP-001',
                'barcode' => '4800016641503',
                'cost_price' => 10,
                'selling_price' => 20,
                'unit' => 'piece',
                'stock_quantity' => 10,
                'minimum_stock' => 2,
            ]);

        $response->assertSessionHasErrors('barcode');
    }

    public function test_a_products_own_barcode_does_not_trigger_the_uniqueness_error_on_update(): void
    {
        $product = Product::factory()->create(['barcode' => '4800016641503']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/store/products/{$product->id}", [
                'category_id' => $product->category_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => '4800016641503',
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'unit' => $product->unit,
                'stock_quantity' => $product->stock_quantity,
                'minimum_stock' => $product->minimum_stock,
            ]);

        $response->assertRedirect(route('admin.store.products.index'));
        $response->assertSessionDoesntHaveErrors('barcode');
    }

    public function test_product_requires_a_category_name_and_sku(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/products', ['name' => '']);

        $response->assertSessionHasErrors(['category_id', 'name', 'sku']);
        $this->assertSame(0, Product::count());
    }

    public function test_sku_must_be_unique(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['sku' => 'COF-001']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/products', [
                'category_id' => $category->id,
                'name' => 'Another Coffee',
                'sku' => 'COF-001',
                'cost_price' => 50,
                'selling_price' => 100,
                'unit' => 'cup',
                'stock_quantity' => 10,
                'minimum_stock' => 2,
            ]);

        $response->assertSessionHasErrors('sku');
        $this->assertSame(1, Product::count());
    }

    public function test_a_products_own_sku_does_not_trigger_the_uniqueness_error_on_update(): void
    {
        $product = Product::factory()->create(['sku' => 'COF-001']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/store/products/{$product->id}", [
                'category_id' => $product->category_id,
                'name' => 'Cafe Cubito',
                'sku' => 'COF-001',
                'cost_price' => 75,
                'selling_price' => 165,
                'unit' => 'cup',
                'stock_quantity' => 24,
                'minimum_stock' => 5,
            ]);

        $response->assertRedirect(route('admin.store.products.index'));
        $response->assertSessionDoesntHaveErrors('sku');
    }

    public function test_admin_can_deactivate_and_reactivate_a_product(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/store/products/{$product->id}/toggle-active");
        $this->assertFalse($product->fresh()->is_active);

        $this->actingAs($admin)->patch("/admin/store/products/{$product->id}/toggle-active");
        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_deactivating_a_product_does_not_delete_it(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/admin/store/products/{$product->id}/toggle-active");

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_the_active_list_hides_inactive_products_by_default(): void
    {
        $active = Product::factory()->create(['name' => 'Active Product']);
        $inactive = Product::factory()->inactive()->create(['name' => 'Inactive Product']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/products');

        $response->assertSee('Active Product');
        $response->assertDontSee('Inactive Product');
    }

    public function test_inactive_products_are_reachable_via_the_status_filter(): void
    {
        $inactive = Product::factory()->inactive()->create(['name' => 'Inactive Product']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/products?status=inactive');

        $response->assertSee('Inactive Product');
    }

    public function test_products_can_be_searched_by_name_or_sku(): void
    {
        $target = Product::factory()->create(['name' => 'Cafe Cubito', 'sku' => 'COF-001']);
        $other = Product::factory()->create(['name' => 'Bottled Water', 'sku' => 'WTR-001']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/products?q=Cubito');

        $response->assertSee('Cafe Cubito');
        $response->assertDontSee('Bottled Water');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $coffee = ProductCategory::factory()->create(['name' => 'Coffee']);
        $snacks = ProductCategory::factory()->create(['name' => 'Snacks']);
        Product::factory()->create(['category_id' => $coffee->id, 'name' => 'Cafe Cubito']);
        Product::factory()->create(['category_id' => $snacks->id, 'name' => 'Chips']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/store/products?category_id={$coffee->id}");

        $response->assertSee('Cafe Cubito');
        $response->assertDontSee('Chips');
    }

    public function test_low_stock_and_out_of_stock_badges_show_on_the_list(): void
    {
        $lowStock = Product::factory()->lowStock()->create(['name' => 'Low Stock Item']);
        $outOfStock = Product::factory()->outOfStock()->create(['name' => 'Out of Stock Item']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/products');

        $response->assertSee('Low Stock');
        $response->assertSee('Out of Stock');
    }

    public function test_staff_cannot_manage_products(): void
    {
        $product = Product::factory()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/store/products')->assertForbidden();
        $this->actingAs($staff)->post('/admin/store/products', [])->assertForbidden();
        $this->actingAs($staff)->patch("/admin/store/products/{$product->id}/toggle-active")->assertForbidden();
    }
}
