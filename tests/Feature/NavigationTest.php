<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_and_register_links_on_home(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Register');
    }

    public function test_customer_sees_customer_navigation(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/')
            ->assertOk()
            ->assertSee('Book a Court')
            ->assertSee('My Bookings')
            ->assertDontSee('Settings');
    }

    public function test_staff_sees_staff_navigation(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('Walk-in Booking')
            ->assertDontSee('Book a Court');
    }

    public function test_admin_sees_admin_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Reports')
            ->assertDontSee('Book a Court');
    }
}
