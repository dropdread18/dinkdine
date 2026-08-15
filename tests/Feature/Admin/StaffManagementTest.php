<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_staff_management(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/staff')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/staff')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/staff')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_staff_list(): void
    {
        User::factory()->staff()->create(['name' => 'Jordan Reyes']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/staff')
            ->assertOk()
            ->assertSee('Jordan Reyes');
    }

    public function test_staff_list_excludes_customers_but_includes_admins(): void
    {
        User::factory()->customer()->create(['name' => 'Some Customer']);
        User::factory()->admin()->create(['name' => 'Another Admin']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/staff');

        $response->assertDontSee('Some Customer');
        $response->assertSee('Another Admin');
    }

    public function test_admin_can_create_a_staff_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'phone' => '0917-111-1111',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ]);

        $response->assertRedirect('/admin/staff');
        $staff = User::where('email', 'newstaff@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertSame('staff', $staff->role->value);
        $this->assertTrue($staff->is_active);
    }

    public function test_admin_can_create_another_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'role' => 'admin',
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ]);

        $response->assertRedirect('/admin/staff');
        $newAdmin = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($newAdmin);
        $this->assertSame('admin', $newAdmin->role->value);
    }

    public function test_staff_creation_requires_matching_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/staff', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'newstaff@example.com']);
    }

    public function test_admin_can_update_a_staff_account(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put("/admin/staff/{$staff->id}", [
            'name' => 'Updated Name',
            'email' => $staff->email,
            'phone' => '0917-222-2222',
        ]);

        $response->assertRedirect('/admin/staff');
        $this->assertSame('Updated Name', $staff->fresh()->name);
    }

    public function test_updating_a_customer_account_through_the_staff_route_is_not_found(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/staff/{$customer->id}/edit")
            ->assertNotFound();
    }

    public function test_admin_can_disable_and_enable_a_staff_account(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/staff/{$staff->id}/toggle-active");
        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($admin)->patch("/admin/staff/{$staff->id}/toggle-active");
        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_admin_cannot_disable_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch("/admin/staff/{$admin->id}/toggle-active");

        $response->assertSessionHasErrors('staff');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_disabling_a_staff_account_prevents_them_from_logging_in(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/staff/{$staff->id}/toggle-active");
        $this->post('/logout');

        $response = $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
