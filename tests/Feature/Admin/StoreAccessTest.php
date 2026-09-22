<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Store module, per the Claude Code Handoff Specification: admin-only
 * access to everything except POS itself, which staff also gets (section
 * 35, "Future Staff POS Permission") - a cashier can sell products but
 * can't touch Products/Categories/Inventory/Sales History/Reports.
 * Everyone else gets a 403 - including on a direct URL visit, not just a
 * hidden nav link. Reuses the existing role: route middleware, same
 * mechanism as every other admin-only section (Courts, Customers, Staff, ...).
 */
class StoreAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_ONLY_ROUTES = [
        '/admin/store',
        '/admin/store/products',
        '/admin/store/categories',
        '/admin/store/inventory',
        '/admin/store/sales',
        '/admin/store/reports',
    ];

    private const STAFF_ACCESSIBLE_ROUTES = [
        '/admin/store/pos',
    ];

    private const ALL_ROUTES = [
        ...self::ADMIN_ONLY_ROUTES,
        ...self::STAFF_ACCESSIBLE_ROUTES,
    ];

    public function test_admin_can_access_every_store_route(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (self::ALL_ROUTES as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }
    }

    public function test_staff_cannot_access_admin_only_store_routes(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (self::ADMIN_ONLY_ROUTES as $route) {
            $this->actingAs($staff)->get($route)->assertForbidden();
        }
    }

    public function test_staff_can_access_pos(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (self::STAFF_ACCESSIBLE_ROUTES as $route) {
            $this->actingAs($staff)->get($route)->assertOk();
        }
    }

    public function test_organizer_cannot_access_any_store_route(): void
    {
        $organizer = User::factory()->organizer()->create();

        foreach (self::ALL_ROUTES as $route) {
            $this->actingAs($organizer)->get($route)->assertForbidden();
        }
    }

    public function test_customer_cannot_access_any_store_route(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (self::ALL_ROUTES as $route) {
            $this->actingAs($customer)->get($route)->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login_for_every_store_route(): void
    {
        foreach (self::ALL_ROUTES as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    public function test_admin_sees_store_navigation(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Store');
    }

    public function test_staff_sees_pos_navigation_but_not_the_store_label(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('POS')
            ->assertDontSee('Store');
    }

    public function test_organizer_does_not_see_store_navigation(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get('/manage/schedule')
            ->assertOk()
            ->assertDontSee('Store')
            // A bare "POS" text assertion risks a false positive against a
            // random CSRF token containing that substring - the route
            // path is unambiguous and just as conclusive.
            ->assertDontSee('admin/store/pos', false);
    }

    public function test_customer_does_not_see_store_navigation(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get('/')
            ->assertOk()
            ->assertDontSee('Store')
            ->assertDontSee('admin/store/pos', false);
    }
}
