<?php

namespace Tests\Feature\Admin\Store;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_categories_list(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Coffee']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/categories')
            ->assertOk()
            ->assertSee('Coffee');
    }

    public function test_admin_can_create_a_category(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/categories', [
                'name' => 'Snacks',
                'description' => 'Chips and packaged snacks',
            ]);

        $response->assertRedirect(route('admin.store.categories.index'));
        $this->assertDatabaseHas('product_categories', ['name' => 'Snacks', 'is_active' => true]);
    }

    public function test_category_name_is_required(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/store/categories', ['name' => '']);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, ProductCategory::count());
    }

    public function test_admin_can_edit_a_category(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Coffee']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/store/categories/{$category->id}", [
                'name' => 'Hot Drinks',
                'description' => null,
            ]);

        $response->assertRedirect(route('admin.store.categories.index'));
        $this->assertSame('Hot Drinks', $category->fresh()->name);
    }

    public function test_admin_can_deactivate_and_reactivate_a_category(): void
    {
        $category = ProductCategory::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/store/categories/{$category->id}/toggle-active");
        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($admin)->patch("/admin/store/categories/{$category->id}/toggle-active");
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_deactivating_a_category_does_not_delete_it_or_its_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/admin/store/categories/{$category->id}/toggle-active");

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $category->id]);
    }

    public function test_staff_cannot_manage_categories(): void
    {
        $category = ProductCategory::factory()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/store/categories')->assertForbidden();
        $this->actingAs($staff)->post('/admin/store/categories', ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($staff)->patch("/admin/store/categories/{$category->id}/toggle-active")->assertForbidden();
    }
}
