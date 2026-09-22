<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 of the Store module (POS/inventory foundation, per the Claude
 * Code Handoff Specification): admin-only access, everyone else gets a
 * 403 - including on a direct URL visit, not just a hidden nav link.
 * Reuses the existing role:admin route middleware, same mechanism as
 * every other admin-only section (Courts, Customers, Staff, ...).
 */
class StoreAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTES = [
        '/admin/store',
        '/admin/store/pos',
        '/admin/store/products',
        '/admin/store/categories',
        '/admin/store/inventory',
        '/admin/store/sales',
        '/admin/store/reports',
    ];

    public function test_admin_can_access_every_store_route(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (self::ROUTES as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }
    }

    public function test_staff_cannot_access_any_store_route(): void
    {
        $staff = User::factory()->staff()->create();

        foreach (self::ROUTES as $route) {
            $this->actingAs($staff)->get($route)->assertForbidden();
        }
    }

    public function test_organizer_cannot_access_any_store_route(): void
    {
        $organizer = User::factory()->organizer()->create();

        foreach (self::ROUTES as $route) {
            $this->actingAs($organizer)->get($route)->assertForbidden();
        }
    }

    public function test_customer_cannot_access_any_store_route(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (self::ROUTES as $route) {
            $this->actingAs($customer)->get($route)->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login_for_every_store_route(): void
    {
        foreach (self::ROUTES as $route) {
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

    public function test_staff_does_not_see_store_navigation(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/staff/dashboard')
            ->assertOk()
            ->assertDontSee('Store');
    }

    public function test_organizer_does_not_see_store_navigation(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get('/manage/schedule')
            ->assertOk()
            ->assertDontSee('Store');
    }

    public function test_customer_does_not_see_store_navigation(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get('/')
            ->assertOk()
            ->assertDontSee('Store');
    }
}
