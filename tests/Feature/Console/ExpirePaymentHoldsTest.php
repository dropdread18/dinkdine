<?php

namespace Tests\Feature\Console;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePaymentHoldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_a_pending_booking_whose_hold_has_lapsed(): void
    {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);
        $booking->forceFill(['hold_expires_at' => now()->subMinute()])->save();

        $this->artisan('bookings:expire-payment-holds')->assertSuccessful();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
    }

    public function test_does_not_expire_a_hold_still_within_its_window(): void
    {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Pending,
        ]);
        $booking->forceFill(['hold_expires_at' => now()->addMinutes(5)])->save();

        $this->artisan('bookings:expire-payment-holds')->assertSuccessful();

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_does_not_touch_a_confirmed_booking(): void
    {
        $booking = Booking::factory()->create([
            'status' => BookingStatus::Confirmed,
        ]);

        $this->artisan('bookings:expire-payment-holds')->assertSuccessful();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_ignores_a_pending_booking_with_no_hold_expiry(): void
    {
        $booking = Booking::factory()->pending()->create();

        $this->artisan('bookings:expire-payment-holds')->assertSuccessful();

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_running_twice_is_safe(): void
    {
        $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);
        $booking->forceFill(['hold_expires_at' => now()->subMinute()])->save();

        $this->artisan('bookings:expire-payment-holds');
        $this->artisan('bookings:expire-payment-holds')->assertSuccessful();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
    }
}
