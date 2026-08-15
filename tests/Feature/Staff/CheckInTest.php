<?php

namespace Tests\Feature\Staff;

use App\Enums\BookingStatus;
use App\Enums\CourtStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_check_in(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get('/manage/check-in')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/manage/check-in')->assertRedirect('/login');
    }

    public function test_staff_and_admin_can_view_the_check_in_page(): void
    {
        $court = Court::factory()->create(['name' => 'Court 5']);
        Booking::factory()->create(['court_id' => $court->id, 'booking_date' => now()->toDateString()]);

        $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/check-in')
            ->assertOk()
            ->assertSee('Court 5');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/manage/check-in')
            ->assertOk();
    }

    public function test_only_todays_bookings_are_listed(): void
    {
        Booking::factory()->create(['booking_date' => now()->toDateString(), 'notes' => 'today-booking']);
        Booking::factory()->create(['booking_date' => now()->addDays(5)->toDateString(), 'notes' => 'future-booking']);

        $response = $this->actingAs(User::factory()->staff()->create())->get('/manage/check-in');

        $response->assertViewHas('bookings', fn ($bookings) => $bookings->count() === 1);
    }

    public function test_search_filters_todays_bookings_by_customer_name(): void
    {
        $match = User::factory()->customer()->create(['name' => 'Juan Dela Cruz']);
        $other = User::factory()->customer()->create(['name' => 'Maria Santos']);
        Booking::factory()->create(['user_id' => $match->id, 'booking_date' => now()->toDateString()]);
        Booking::factory()->create(['user_id' => $other->id, 'booking_date' => now()->toDateString()]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/check-in?q=Juan');

        $response->assertOk();
        $response->assertViewHas('bookings', fn ($bookings) => $bookings->count() === 1
            && $bookings->first()->user_id === $match->id);
    }

    public function test_search_filters_todays_bookings_by_booking_number(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);
        Booking::factory()->create(['booking_date' => now()->toDateString()]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->get('/manage/check-in?q=PB-'.$booking->id);

        $response->assertOk();
        $response->assertViewHas('bookings', fn ($bookings) => $bookings->count() === 1
            && $bookings->first()->id === $booking->id);
    }

    public function test_staff_can_check_in_a_booking(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/check-in/bookings/{$booking->id}/check-in");

        $response->assertRedirect();
        $this->assertNotNull($booking->fresh()->checked_in_at);
    }

    public function test_checking_in_an_already_checked_in_booking_fails_gracefully(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->patch("/manage/check-in/bookings/{$booking->id}/check-in");
        $response = $this->actingAs($staff)->patch("/manage/check-in/bookings/{$booking->id}/check-in");

        $response->assertSessionHasErrors('booking');
    }

    public function test_staff_can_mark_a_booking_completed(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);

        $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/check-in/bookings/{$booking->id}/complete");

        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_staff_can_mark_a_booking_no_show(): void
    {
        $booking = Booking::factory()->create(['booking_date' => now()->toDateString()]);

        $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/check-in/bookings/{$booking->id}/no-show");

        $this->assertSame(BookingStatus::NoShow, $booking->fresh()->status);
    }

    public function test_marking_a_cancelled_booking_completed_fails_gracefully(): void
    {
        $booking = Booking::factory()->cancelled()->create(['booking_date' => now()->toDateString()]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/check-in/bookings/{$booking->id}/complete");

        $response->assertSessionHasErrors('booking');
    }

    public function test_staff_can_update_a_courts_status(): void
    {
        $court = Court::factory()->create(['status' => CourtStatus::Active]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->patch("/manage/check-in/courts/{$court->id}/status", ['status' => 'maintenance']);

        $response->assertRedirect();
        $this->assertSame(CourtStatus::Maintenance, $court->fresh()->status);
    }
}
