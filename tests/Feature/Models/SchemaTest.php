<?php

namespace Tests\Feature\Models;

use App\Enums\BookingStatus;
use App\Enums\CourtStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtMaintenance;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_belongs_to_user_and_court(): void
    {
        $booking = Booking::factory()->create();

        $this->assertInstanceOf(User::class, $booking->user);
        $this->assertInstanceOf(Court::class, $booking->court);
        $this->assertTrue($booking->user->isCustomer());
    }

    public function test_court_has_many_bookings(): void
    {
        $court = Court::factory()->create();
        Booking::factory()->count(3)->create(['court_id' => $court->id]);

        $this->assertCount(3, $court->bookings);
    }

    public function test_user_has_many_bookings(): void
    {
        $user = User::factory()->customer()->create();
        Booking::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->bookings);
    }

    public function test_booking_has_one_payment(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $this->assertTrue($booking->payment->is($payment));
        $this->assertTrue($payment->booking->is($booking));
    }

    public function test_court_has_many_maintenance_periods(): void
    {
        $court = Court::factory()->create();
        CourtMaintenance::factory()->count(2)->create(['court_id' => $court->id]);

        $this->assertCount(2, $court->maintenancePeriods);
    }

    public function test_court_status_enum_casts_and_reports_bookability(): void
    {
        $active = Court::factory()->create();
        $maintenance = Court::factory()->maintenance()->create();

        $this->assertSame(CourtStatus::Active, $active->status);
        $this->assertTrue($active->isBookable());
        $this->assertFalse($maintenance->isBookable());
    }

    public function test_booking_status_and_payment_status_enums_cast(): void
    {
        $booking = Booking::factory()->pending()->create();

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame(PaymentStatus::Unpaid, $booking->payment_status);
        $this->assertTrue($booking->status->blocksAvailability());
    }

    public function test_schema_does_not_yet_prevent_overlapping_bookings(): void
    {
        // Requirements.md §14 requires double-booking protection, but that's
        // application logic (BookingService, Phase 5), not a schema
        // constraint. This test documents the current gap on purpose — when
        // conflict detection is built, this should start failing and get
        // rewritten to assert the conflicting create() throws instead.
        $court = Court::factory()->create();
        $attrs = [
            'court_id' => $court->id,
            'booking_date' => '2026-09-01',
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
        ];

        Booking::factory()->create($attrs);
        Booking::factory()->create($attrs);

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_setting_get_and_set_helpers(): void
    {
        Setting::set('max_advance_booking_days', '30');

        $this->assertSame('30', Setting::get('max_advance_booking_days'));
        $this->assertSame('fallback', Setting::get('missing_key', 'fallback'));
    }
}
