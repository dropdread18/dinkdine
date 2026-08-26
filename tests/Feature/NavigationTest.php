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
            ->assertSee('Profile')
            ->assertDontSee('Settings');
    }

    public function test_guest_booker_does_not_see_a_profile_link(): void
    {
        // A guest/walk-in account never chose a password - Profile has
        // nothing real for them to do, so don't advertise it in the nav.
        $guest = User::factory()->customer()->guestBooker()->create();

        $this->actingAs($guest)->get('/')
            ->assertOk()
            ->assertDontSee('Profile');
    }

    public function test_staff_sees_staff_navigation(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('Walk-in Booking')
            ->assertDontSee('Book a Court');
    }

    public function test_organizer_sees_only_bookings_and_open_play(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->get('/manage/schedule')
            ->assertOk()
            ->assertSee('Schedule')
            ->assertSee('Open Play')
            ->assertDontSee('Payments')
            ->assertDontSee('Reports')
            ->assertDontSee('Settings')
            ->assertDontSee('Walk-in Booking')
            ->assertDontSee('Check-in')
            ->assertDontSee('Customers');
    }

    public function test_admin_sees_admin_navigation(): void
    {
        // Settings/Maintenance/Staff are deliberately not on the dashboard's
        // own sidebar (mockup-driven, matches Admin Dashboard.dc.html's 6
        // nav items) - still reachable via the top nav on every other admin
        // page, which this route no longer renders since it's now a
        // standalone Dink Dine-branded page like the booking flow.
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Bookings')
            ->assertSee('Reports')
            ->assertDontSee('Book a Court');
    }
}
