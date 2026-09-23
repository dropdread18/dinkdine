<?php

namespace Tests\Feature\Admin\Store;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_inventory_list(): void
    {
        $product = Product::factory()->create(['name' => 'Cafe Cubito', 'stock_quantity' => 24]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/inventory')
            ->assertOk()
            ->assertSee('Cafe Cubito')
            ->assertSee('24');
    }

    public function test_low_stock_and_out_of_stock_badges_show_on_the_inventory_list(): void
    {
        $low = Product::factory()->lowStock()->create(['name' => 'Low Item']);
        $out = Product::factory()->outOfStock()->create(['name' => 'Out Item']);
        $ok = Product::factory()->create(['name' => 'OK Item', 'stock_quantity' => 50, 'minimum_stock' => 5]);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/store/inventory');

        $response->assertSee('Low Stock');
        $response->assertSee('Out of Stock');
        $response->assertSee('OK');
    }

    public function test_needs_restocking_filter_only_shows_low_and_out_of_stock_products(): void
    {
        Product::factory()->lowStock()->create(['name' => 'Low Item']);
        Product::factory()->create(['name' => 'OK Item', 'stock_quantity' => 50, 'minimum_stock' => 5]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/inventory?low_stock=1');

        $response->assertSee('Low Item');
        $response->assertDontSee('OK Item');
    }

    public function test_inventory_can_be_searched_by_scanned_barcode(): void
    {
        Product::factory()->create(['name' => 'Target Item', 'barcode' => '4800016641503']);
        Product::factory()->create(['name' => 'Other Item', 'barcode' => '4800016649999']);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/store/inventory?q=4800016641503');

        $response->assertSee('Target Item');
        $response->assertDontSee('Other Item');
    }

    public function test_admin_can_record_a_stock_in_movement(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post("/admin/store/inventory/{$product->id}/stock-in", [
            'quantity' => 20,
            'reason' => 'New delivery',
        ]);

        $response->assertRedirect(route('admin.store.inventory.index'));
        $this->assertSame(44, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovementType::StockIn->value,
            'quantity' => 20,
            'previous_quantity' => 24,
            'new_quantity' => 44,
            'reason' => 'New delivery',
            'user_id' => $admin->id,
        ]);
    }

    public function test_stock_in_quantity_must_be_positive(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/store/inventory/{$product->id}/stock-in", ['quantity' => 0]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(24, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_admin_can_record_a_damaged_adjustment(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post("/admin/store/inventory/{$product->id}/adjust", [
            'type' => 'damaged',
            'quantity_change' => -3,
            'reason' => 'Dropped during prep',
        ]);

        $response->assertRedirect(route('admin.store.inventory.index'));
        $this->assertSame(21, $product->fresh()->stock_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => InventoryMovementType::Damaged->value,
            'quantity' => -3,
            'previous_quantity' => 24,
            'new_quantity' => 21,
            'user_id' => $admin->id,
        ]);
    }

    public function test_a_positive_adjustment_increases_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);

        $this->actingAs(User::factory()->admin()->create())->post("/admin/store/inventory/{$product->id}/adjust", [
            'type' => 'adjustment',
            'quantity_change' => 5,
            'reason' => 'Physical count found more',
        ]);

        $this->assertSame(29, $product->fresh()->stock_quantity);
    }

    public function test_an_adjustment_cannot_take_stock_negative(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $response = $this->actingAs(User::factory()->admin()->create())->post("/admin/store/inventory/{$product->id}/adjust", [
            'type' => 'lost',
            'quantity_change' => -10,
        ]);

        $response->assertSessionHasErrors('quantity_change');
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_adjustment_quantity_change_cannot_be_zero(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post("/admin/store/inventory/{$product->id}/adjust", [
            'type' => 'adjustment',
            'quantity_change' => 0,
        ]);

        $response->assertSessionHasErrors('quantity_change');
    }

    public function test_sale_and_returned_are_not_selectable_adjustment_types(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->post("/admin/store/inventory/{$product->id}/adjust", [
            'type' => 'sale',
            'quantity_change' => -1,
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_inventory_history_lists_movements_newest_first(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 24]);
        $admin = User::factory()->admin()->create();

        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'type' => InventoryMovementType::StockIn,
            'reason' => 'First delivery',
            'created_at' => now()->subDays(2),
        ]);
        InventoryMovement::factory()->create([
            'product_id' => $product->id,
            'type' => InventoryMovementType::Damaged,
            'reason' => 'Recent breakage',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get("/admin/store/inventory/{$product->id}/history");

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'First delivery'), strpos($content, 'Recent breakage'));
    }

    public function test_staff_cannot_access_any_inventory_route(): void
    {
        $product = Product::factory()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/store/inventory')->assertForbidden();
        $this->actingAs($staff)->get("/admin/store/inventory/{$product->id}/stock-in")->assertForbidden();
        $this->actingAs($staff)->post("/admin/store/inventory/{$product->id}/stock-in", ['quantity' => 5])->assertForbidden();
        $this->actingAs($staff)->get("/admin/store/inventory/{$product->id}/adjust")->assertForbidden();
        $this->actingAs($staff)->post("/admin/store/inventory/{$product->id}/adjust", ['type' => 'lost', 'quantity_change' => -1])->assertForbidden();
        $this->actingAs($staff)->get("/admin/store/inventory/{$product->id}/history")->assertForbidden();

        $this->assertSame(0, InventoryMovement::count());
    }
}
