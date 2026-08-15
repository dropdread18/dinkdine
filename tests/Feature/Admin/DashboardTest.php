<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pinned mid-afternoon, not the real current time - the dashboard's
        // "occupied right now" / "still upcoming" queries compare against
        // now(), and building fixture times relative to a real wall-clock
        // now() risks the same midnight-rollover flakiness documented
        // elsewhere in this suite (a subtracted/added time crossing into a
        // different calendar date than the fixed booking_date).
        Carbon::setTestNow(Carbon::today()->setTime(14, 0));

        BusinessHour::create([
            'day_of_week' => Carbon::today()->dayOfWeek,
            'opens_at' => '06:00:00',
            'closes_at' => '22:00:00',
            'is_closed' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shows_todays_revenue_and_booking_count(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();

        $paidBooking = Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => Carbon::today(),
            'status' => BookingStatus::Confirmed,
            'price' => 500,
        ]);
        Payment::factory()->create([
            'booking_id' => $paidBooking->id,
            'status' => PaymentStatus::Paid,
            'amount' => 500,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('₱500');
        $response->assertSee("Today's Bookings", false);
    }

    public function test_shows_only_currently_occupied_courts(): void
    {
        $admin = User::factory()->admin()->create();
        $occupiedCourt = Court::factory()->create(['name' => 'Occupied Court']);
        $freeCourt = Court::factory()->create(['name' => 'Free Court']);

        $now = now();

        Booking::factory()->create([
            'court_id' => $occupiedCourt->id,
            'booking_date' => Carbon::today(),
            'status' => BookingStatus::Confirmed,
            'start_time' => $now->copy()->subMinutes(30)->format('H:i:s'),
            'end_time' => $now->copy()->addMinutes(30)->format('H:i:s'),
        ]);

        Booking::factory()->create([
            'court_id' => $freeCourt->id,
            'booking_date' => Carbon::today(),
            'status' => BookingStatus::Confirmed,
            'start_time' => $now->copy()->addHours(3)->format('H:i:s'),
            'end_time' => $now->copy()->addHours(4)->format('H:i:s'),
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('1 / 2');
    }

    public function test_shows_pending_payments_count(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $booking->id, 'status' => PaymentStatus::Pending]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Pending Payments');
    }

    public function test_upcoming_bookings_excludes_ones_that_already_ended(): void
    {
        $admin = User::factory()->admin()->create();
        $court = Court::factory()->create();
        $customer = User::factory()->customer()->create(['name' => 'Still Upcoming']);
        $pastCustomer = User::factory()->customer()->create(['name' => 'Already Finished']);

        $now = now();

        Booking::factory()->create([
            'court_id' => $court->id,
            'user_id' => $customer->id,
            'booking_date' => Carbon::today(),
            'status' => BookingStatus::Confirmed,
            'start_time' => $now->copy()->addHour()->format('H:i:s'),
            'end_time' => $now->copy()->addHours(2)->format('H:i:s'),
        ]);

        Booking::factory()->create([
            'court_id' => $court->id,
            'user_id' => $pastCustomer->id,
            'booking_date' => Carbon::today(),
            'status' => BookingStatus::Confirmed,
            'start_time' => $now->copy()->subHours(3)->format('H:i:s'),
            'end_time' => $now->copy()->subHours(2)->format('H:i:s'),
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Still Upcoming');
        $response->assertDontSee('Already Finished');
    }

    public function test_shows_court_utilization_percentages(): void
    {
        $admin = User::factory()->admin()->create();
        Court::factory()->create(['name' => 'Utilization Court']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Utilization Court');
        $response->assertSee('Court Utilization');
    }

    public function test_staff_sees_the_same_dashboard_at_their_own_route(): void
    {
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();
        Payment::factory()->create(['booking_id' => $booking->id, 'status' => PaymentStatus::Pending]);

        $response = $this->actingAs($staff)->get('/staff/dashboard');

        $response->assertOk();
        $response->assertSee('Pending Payments');
    }

    public function test_customer_cannot_view_either_dashboard_route(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($customer)->get('/staff/dashboard')->assertForbidden();
    }
}
