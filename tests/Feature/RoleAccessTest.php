<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_staff_cannot_access_admin_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_staff_and_admin_can_access_staff_dashboard(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)->get('/staff/dashboard')->assertOk();
        $this->actingAs($admin)->get('/staff/dashboard')->assertOk();
    }

    public function test_customer_cannot_access_staff_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/staff/dashboard')->assertForbidden();
    }

    public function test_default_role_on_registration_is_customer(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isCustomer());
        $this->assertSame(UserRole::Customer, $user->role);
    }
}
