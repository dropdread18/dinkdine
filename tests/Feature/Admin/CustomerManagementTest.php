<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_staff_cannot_access_customer_management(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/admin/customers')->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get('/admin/customers')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/customers')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_customer_list(): void
    {
        User::factory()->customer()->create(['name' => 'Jamie Rivera']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee('Jamie Rivera');
    }

    public function test_customer_list_excludes_staff_and_admin_accounts(): void
    {
        User::factory()->staff()->create(['name' => 'Staff Member']);
        User::factory()->admin()->create(['name' => 'Admin Person']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/customers');

        $response->assertDontSee('Staff Member');
        $response->assertDontSee('Admin Person');
    }

    public function test_admin_can_search_customers(): void
    {
        User::factory()->customer()->create(['name' => 'Alex Cruz', 'email' => 'alex@example.com']);
        User::factory()->customer()->create(['name' => 'Sam Dela Cruz', 'email' => 'sam@example.com']);

        $response = $this->actingAs(User::factory()->admin()->create())->get('/admin/customers?q=alex');

        $response->assertSee('Alex Cruz');
        $response->assertDontSee('Sam Dela Cruz');
    }

    public function test_admin_can_create_a_customer(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/admin/customers', [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'phone' => '0917-000-0000',
        ]);

        $customer = User::where('email', 'newcustomer@example.com')->first();
        $response->assertRedirect("/admin/customers/{$customer->id}");
        $this->assertSame('customer', $customer->role->value);
        $this->assertTrue($customer->is_active);
    }

    public function test_admin_can_view_a_customer_profile_with_booking_stats(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $completed = Booking::factory()->create(['user_id' => $customer->id, 'status' => BookingStatus::Completed]);
        Booking::factory()->create(['user_id' => $customer->id, 'status' => BookingStatus::Cancelled]);
        Payment::factory()->paid()->create(['booking_id' => $completed->id, 'amount' => 500]);

        $response = $this->actingAs($admin)->get("/admin/customers/{$customer->id}");

        $response->assertOk();
        $response->assertSee('500.00');
        $response->assertViewHas('totalBookings', 2);
        $response->assertViewHas('completedBookings', 1);
        $response->assertViewHas('cancelledBookings', 1);
        $response->assertViewHas('totalSpent', 500.0);
    }

    public function test_viewing_a_staff_account_through_the_customer_route_is_not_found(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/customers/{$staff->id}")
            ->assertNotFound();
    }

    public function test_admin_can_disable_and_enable_a_customer_account(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/customers/{$customer->id}/toggle-active");
        $this->assertFalse($customer->fresh()->is_active);

        $this->actingAs($admin)->patch("/admin/customers/{$customer->id}/toggle-active");
        $this->assertTrue($customer->fresh()->is_active);
    }

    public function test_disabling_a_customer_prevents_them_from_logging_in(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/customers/{$customer->id}/toggle-active");
        $this->post('/logout');

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_convert_a_customer_to_staff(): void
    {
        $customer = User::factory()->customer()->create(['is_active' => false]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch("/admin/customers/{$customer->id}/convert-to-staff");

        $response->assertRedirect("/admin/staff/{$customer->id}/edit");
        $customer->refresh();
        $this->assertTrue($customer->role === \App\Enums\UserRole::Staff);
        $this->assertTrue($customer->is_active);
    }

    public function test_converting_a_customer_to_staff_preserves_their_booking_history(): void
    {
        $customer = User::factory()->customer()->create();
        Booking::factory()->create(['user_id' => $customer->id]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch("/admin/customers/{$customer->id}/convert-to-staff");

        $this->assertSame(1, Booking::where('user_id', $customer->id)->count());
    }

    public function test_converting_a_staff_account_through_the_customer_route_is_not_found(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/admin/customers/{$staff->id}/convert-to-staff")
            ->assertNotFound();
    }
}
