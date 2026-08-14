<?php

namespace Tests\Feature\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\CourtStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Court;
use App\Models\CourtMaintenance;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingService(new AvailabilityService);

        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '30');
        Setting::set('max_advance_booking_days', '30');

        $this->date = CarbonImmutable::now()->addDays(2)->toDateString();

        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($this->date)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '20:00:00',
            'is_closed' => false,
        ]);
    }

    public function test_creates_a_confirmed_booking_with_calculated_price(): void
    {
        $user = User::factory()->customer()->create();
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $booking = $this->service->book($user, $court, $this->date, '09:00:00', '10:00:00');

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Unpaid, $booking->payment_status);
        $this->assertSame(BookingSource::Online, $booking->source);
        $this->assertEquals(300.00, (float) $booking->price);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'user_id' => $user->id, 'court_id' => $court->id]);
    }

    public function test_price_scales_with_configured_slot_duration(): void
    {
        // Slot duration is fixed by settings (Requirements.md §15 - MVP
        // only supports fixed slots), so a 90-minute booking must come
        // from a 90-minute-configured slot, not an arbitrary time range.
        Setting::set('default_booking_duration_minutes', '90');

        $user = User::factory()->customer()->create();
        $court = Court::factory()->create(['hourly_rate' => 300]);

        // Slots are generated starting at opens_at (08:00) in 90-minute
        // steps, so the first real slot is 08:00-09:30 - not 09:00-10:30.
        $booking = $this->service->book($user, $court, $this->date, '08:00:00', '09:30:00');

        $this->assertEquals(450.00, (float) $booking->price);
    }

    public function test_records_a_non_default_source_and_notes(): void
    {
        $user = User::factory()->customer()->create();
        $court = Court::factory()->create();

        $booking = $this->service->book(
            $user, $court, $this->date, '09:00:00', '10:00:00',
            notes: 'Birthday game',
            source: BookingSource::WalkIn,
        );

        $this->assertSame(BookingSource::WalkIn, $booking->source);
        $this->assertSame('Birthday game', $booking->notes);
    }

    public function test_rejects_a_slot_that_is_already_booked(): void
    {
        $court = Court::factory()->create();
        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->expectException(BookingUnavailableException::class);

        $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
    }

    public function test_second_conflicting_attempt_fails_after_the_first_succeeds(): void
    {
        // Simulates two customers racing for the same slot. Not a true
        // concurrency test (PHPUnit is single-threaded) - see
        // BookingService's class doc for what this can and can't prove.
        $court = Court::factory()->create();
        $user1 = User::factory()->customer()->create();
        $user2 = User::factory()->customer()->create();

        $this->service->book($user1, $court, $this->date, '09:00:00', '10:00:00');

        $this->expectException(BookingUnavailableException::class);
        $this->service->book($user2, $court, $this->date, '09:00:00', '10:00:00');
    }

    public function test_rejects_a_partially_overlapping_slot(): void
    {
        $court = Court::factory()->create();
        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => $this->date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->expectException(BookingUnavailableException::class);

        $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:30:00', '10:30:00'
        );
    }

    public function test_rejects_booking_during_court_maintenance(): void
    {
        $court = Court::factory()->create();
        CourtMaintenance::factory()->create([
            'court_id' => $court->id,
            'starts_at' => $this->date.' 09:00:00',
            'ends_at' => $this->date.' 11:00:00',
        ]);

        $this->expectException(BookingUnavailableException::class);

        $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
    }

    public function test_rejects_booking_outside_business_hours(): void
    {
        $court = Court::factory()->create();

        $this->expectException(BookingUnavailableException::class);

        // Business hours in setUp are 08:00-20:00.
        $this->service->book(User::factory()->customer()->create(), $court, $this->date, '21:00:00', '22:00:00');
    }

    public function test_rejects_booking_for_an_inactive_court(): void
    {
        $court = Court::factory()->create(['status' => CourtStatus::Maintenance]);

        $this->expectException(BookingUnavailableException::class);

        $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
    }

    public function test_rejects_booking_that_is_too_soon(): void
    {
        $court = Court::factory()->create();

        BusinessHour::query()->delete();
        $today = CarbonImmutable::now()->toDateString();
        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($today)->dayOfWeek,
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:00',
            'is_closed' => false,
        ]);

        $this->expectException(BookingUnavailableException::class);

        // 10 minutes from now, but min notice is 30 minutes.
        $soon = CarbonImmutable::now()->addMinutes(10);
        $this->service->book(
            User::factory()->customer()->create(), $court, $soon->toDateString(), $soon->format('H:i:s'), $soon->addHour()->format('H:i:s')
        );
    }

    public function test_rejects_booking_too_far_in_advance(): void
    {
        $court = Court::factory()->create();
        $farDate = CarbonImmutable::now()->addDays(60);

        BusinessHour::updateOrCreate(
            ['day_of_week' => $farDate->dayOfWeek],
            ['opens_at' => '08:00:00', 'closes_at' => '20:00:00', 'is_closed' => false],
        );

        $this->expectException(BookingUnavailableException::class);

        $this->service->book(
            User::factory()->customer()->create(), $court, $farDate->toDateString(), '09:00:00', '10:00:00'
        );
    }

    public function test_book_can_skip_the_booking_window_for_walk_ins(): void
    {
        // A walk-in is standing at the counter right now; the 30-minute
        // online min-notice rule shouldn't block staff from booking them in.
        // Use the current hour's slot boundary so it lines up with a real
        // generated slot (00:00-aligned, 60-minute slots).
        $court = Court::factory()->create();
        $now = CarbonImmutable::now();
        $slotStart = $now->startOfHour();

        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $slotStart->toDateString(),
            $slotStart->format('H:i:s'), $slotStart->addHour()->format('H:i:s'),
            source: BookingSource::WalkIn,
            enforceBookingWindow: false,
        );

        $this->assertSame(BookingSource::WalkIn, $booking->source);
    }

    public function test_reschedule_moves_a_booking_to_a_new_slot_and_recalculates_price(): void
    {
        $courtA = Court::factory()->create(['hourly_rate' => 300]);
        $courtB = Court::factory()->create(['hourly_rate' => 400]);
        $booking = $this->service->book(User::factory()->customer()->create(), $courtA, $this->date, '09:00:00', '10:00:00');

        $rescheduled = $this->service->reschedule($booking, $courtB, $this->date, '11:00:00', '12:00:00');

        $this->assertSame($courtB->id, $rescheduled->court_id);
        $this->assertSame('11:00:00', $rescheduled->start_time);
        $this->assertEquals(400.00, (float) $rescheduled->price);
    }

    public function test_reschedule_does_not_conflict_with_its_own_current_slot(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        // "Reschedule" to the exact same slot on the same court - must not
        // be rejected as conflicting with itself.
        $rescheduled = $this->service->reschedule($booking, $court, $this->date, '09:00:00', '10:00:00');

        $this->assertSame($booking->id, $rescheduled->id);
    }

    public function test_reschedule_rejects_a_slot_taken_by_another_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->book(User::factory()->customer()->create(), $court, $this->date, '11:00:00', '12:00:00');

        $this->expectException(BookingUnavailableException::class);
        $this->service->reschedule($booking, $court, $this->date, '11:00:00', '12:00:00');
    }

    public function test_cancel_marks_a_booking_cancelled_and_records_the_reason(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $cancelled = $this->service->cancel($booking, 'Customer called to cancel');

        $this->assertSame(BookingStatus::Cancelled, $cancelled->status);
        $this->assertStringContainsString('Customer called to cancel', $cancelled->notes);
    }

    public function test_cancelling_frees_the_slot_for_a_new_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->cancel($booking);

        $newBooking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $this->assertNotSame($booking->id, $newBooking->id);
    }

    public function test_cancel_rejects_an_already_cancelled_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->cancel($booking);

        $this->expectException(BookingUnavailableException::class);
        $this->service->cancel($booking->fresh());
    }
}
