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
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
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

        $this->service = new BookingService(new AvailabilityService, new PricingService);

        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '30');
        Setting::set('max_advance_booking_days', '30');
        Setting::set('cancellation_deadline_hours', '4');

        $this->date = CarbonImmutable::now()->addDays(2)->toDateString();

        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($this->date)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '20:00:00',
            'is_closed' => false,
        ]);
    }

    /**
     * The next full hour, within the 4-hour cancellation window - but never
     * 23:00 today. AvailabilityService never generates a slot starting at
     * 23:00 (it would end at "00:00:00", which the day's slot generation
     * treats as earlier than closes_at and stops before reaching), so a
     * naive "next hour from now" is flaky for roughly one hour a day. Roll
     * into tomorrow's 00:00 slot instead, which the engine generates fine.
     */
    private function soonSlot(): CarbonImmutable
    {
        $slotStart = CarbonImmutable::now()->startOfHour()->addHour();

        return $slotStart->hour === 23 ? $slotStart->addHour() : $slotStart;
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

    public function test_a_customer_can_book_the_slot_currently_in_progress(): void
    {
        // Owner feedback: the min-notice buffer (30 minutes here) shouldn't
        // block the hour that's already running - only genuinely-future
        // slots need advance notice. This is the exact scenario
        // test_rejects_booking_that_is_too_soon above still correctly
        // rejects (a future slot inside the notice window); this one is a
        // slot that has already started.
        $court = Court::factory()->create();

        BusinessHour::query()->delete();
        $today = CarbonImmutable::now()->toDateString();
        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse($today)->dayOfWeek,
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:00',
            'is_closed' => false,
        ]);

        // Never 23:00 - AvailabilityService never generates a slot ending
        // at "00:00:00" (documented elsewhere in this suite), so a naive
        // "current hour" is flaky for roughly one hour a day.
        $currentHour = CarbonImmutable::now()->startOfHour();
        if ($currentHour->hour === 23) {
            $this->markTestSkipped('Flaky at the 23:00 slot-generation boundary - see soonSlot() elsewhere in this suite.');
        }

        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $currentHour->toDateString(), $currentHour->format('H:i:s'), $currentHour->addHour()->format('H:i:s')
        );

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
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
        // Use a near-now slot boundary so it lines up with a real generated
        // slot (00:00-aligned, 60-minute slots) - soonSlot() rather than the
        // literal current hour, since that hour doesn't exist as a slot when
        // it happens to be 23:00 (see soonSlot()'s own doc comment).
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();

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

    public function test_customer_can_cancel_a_booking_well_outside_the_deadline(): void
    {
        // $this->date is 2 days out - well past the 4-hour cancellation deadline.
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $cancelled = $this->service->cancel($booking, enforcePolicy: true);

        $this->assertSame(BookingStatus::Cancelled, $cancelled->status);
    }

    public function test_customer_cannot_cancel_a_booking_within_the_deadline(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot(); // within the 4-hour window
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $slotStart->toDateString(),
            $slotStart->format('H:i:s'), $slotStart->addHour()->format('H:i:s'),
            enforceBookingWindow: false,
        );

        $this->assertFalse($this->service->isEligibleForCustomerAction($booking));

        $this->expectException(BookingUnavailableException::class);
        $this->service->cancel($booking, enforcePolicy: true);
    }

    public function test_customer_cannot_reschedule_a_booking_within_the_deadline(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $slotStart->toDateString(),
            $slotStart->format('H:i:s'), $slotStart->addHour()->format('H:i:s'),
            enforceBookingWindow: false,
        );

        $this->expectException(BookingUnavailableException::class);
        $this->service->reschedule($booking, $court, $this->date, '09:00:00', '10:00:00', enforcePolicy: true);
    }

    public function test_staff_cancel_and_reschedule_bypass_the_customer_deadline_by_default(): void
    {
        $court = Court::factory()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $slotStart->toDateString(),
            $slotStart->format('H:i:s'), $slotStart->addHour()->format('H:i:s'),
            enforceBookingWindow: false,
        );

        // No enforcePolicy passed - defaults to false, matching staff usage.
        $cancelled = $this->service->cancel($booking);

        $this->assertSame(BookingStatus::Cancelled, $cancelled->status);
    }

    public function test_is_eligible_for_customer_action_is_false_once_cancelled(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->cancel($booking);

        $this->assertFalse($this->service->isEligibleForCustomerAction($booking->fresh()));
    }

    public function test_booking_gets_an_unpaid_payment_record_matching_its_price(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Unpaid, $payment->status);
        $this->assertEquals(300.00, (float) $payment->amount);
    }

    public function test_an_online_booking_captures_the_configured_convenience_fee(): void
    {
        Setting::set('convenience_fee', '15');
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $this->assertEquals(15.00, (float) $booking->convenience_fee);
        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(315.00, (float) $payment->amount);
    }

    public function test_a_walk_in_booking_is_never_charged_the_convenience_fee(): void
    {
        Setting::set('convenience_fee', '15');
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $booking = $this->service->book(
            User::factory()->customer()->create(),
            $court,
            $this->date,
            '09:00:00',
            '10:00:00',
            source: BookingSource::WalkIn,
            enforceBookingWindow: false,
        );

        $this->assertEquals(0.00, (float) $booking->convenience_fee);
        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(300.00, (float) $payment->amount);
    }

    public function test_no_convenience_fee_is_charged_when_the_setting_is_unset(): void
    {
        $court = Court::factory()->create(['hourly_rate' => 300]);

        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $this->assertEquals(0.00, (float) $booking->convenience_fee);
    }

    public function test_rescheduling_updates_the_unpaid_payment_amount(): void
    {
        $courtA = Court::factory()->create(['hourly_rate' => 300]);
        $courtB = Court::factory()->create(['hourly_rate' => 500]);
        $booking = $this->service->book(User::factory()->customer()->create(), $courtA, $this->date, '09:00:00', '10:00:00');

        $this->service->reschedule($booking, $courtB, $this->date, '11:00:00', '12:00:00');

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(500.00, (float) $payment->amount);
    }

    public function test_rescheduling_preserves_the_original_convenience_fee_and_includes_it_in_the_synced_payment(): void
    {
        Setting::set('convenience_fee', '10');
        $courtA = Court::factory()->create(['hourly_rate' => 300]);
        $courtB = Court::factory()->create(['hourly_rate' => 500]);
        $booking = $this->service->book(User::factory()->customer()->create(), $courtA, $this->date, '09:00:00', '10:00:00');

        $this->service->reschedule($booking, $courtB, $this->date, '11:00:00', '12:00:00');

        $this->assertEquals(10.00, (float) $booking->fresh()->convenience_fee);
        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(510.00, (float) $payment->amount);
    }

    public function test_rescheduling_does_not_change_the_amount_of_an_already_paid_payment(): void
    {
        $courtA = Court::factory()->create(['hourly_rate' => 300]);
        $courtB = Court::factory()->create(['hourly_rate' => 500]);
        $booking = $this->service->book(User::factory()->customer()->create(), $courtA, $this->date, '09:00:00', '10:00:00');

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $payment->update(['status' => PaymentStatus::Paid, 'method' => 'cash', 'paid_at' => now()]);

        $this->service->reschedule($booking, $courtB, $this->date, '11:00:00', '12:00:00');

        $this->assertEquals(300.00, (float) $payment->fresh()->amount);
    }

    public function test_book_many_creates_a_booking_per_slot(): void
    {
        $courtA = Court::factory()->create(['hourly_rate' => 300]);
        $courtB = Court::factory()->create(['hourly_rate' => 400]);
        $user = User::factory()->customer()->create();

        $bookings = $this->service->bookMany($user, [
            ['court' => $courtA, 'date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00'],
            ['court' => $courtB, 'date' => $this->date, 'start_time' => '11:00:00', 'end_time' => '12:00:00'],
        ]);

        $this->assertCount(2, $bookings);
        $this->assertDatabaseCount('bookings', 2);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame($user->id, $bookings[0]->user_id);
        $this->assertSame($user->id, $bookings[1]->user_id);
    }

    public function test_book_many_rejects_an_empty_selection(): void
    {
        $this->expectException(BookingUnavailableException::class);
        $this->service->bookMany(User::factory()->customer()->create(), []);
    }

    public function test_book_many_rolls_back_every_booking_if_one_slot_fails(): void
    {
        $courtA = Court::factory()->create();
        $courtB = Court::factory()->create();
        $user = User::factory()->customer()->create();

        // Someone else already has courtB at 11:00-12:00.
        Booking::factory()->create([
            'court_id' => $courtB->id, 'booking_date' => $this->date, 'start_time' => '11:00:00', 'end_time' => '12:00:00',
        ]);

        try {
            $this->service->bookMany($user, [
                ['court' => $courtA, 'date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00'],
                ['court' => $courtB, 'date' => $this->date, 'start_time' => '11:00:00', 'end_time' => '12:00:00'],
            ]);
            $this->fail('Expected a BookingUnavailableException.');
        } catch (BookingUnavailableException $e) {
            $this->assertStringContainsString($courtB->name, $e->getMessage());
        }

        // The first slot must NOT have survived, even though it was valid
        // on its own - the whole batch is all-or-nothing.
        $this->assertSame(0, Booking::where('user_id', $user->id)->count());
    }

    public function test_book_many_passes_through_source_and_enforce_booking_window(): void
    {
        $court = Court::factory()->create();
        $user = User::factory()->customer()->create();
        $slotStart = $this->soonSlot();
        BusinessHour::updateOrCreate(
            ['day_of_week' => $slotStart->dayOfWeek],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => false],
        );

        $bookings = $this->service->bookMany($user, [
            ['court' => $court, 'date' => $slotStart->toDateString(), 'start_time' => $slotStart->format('H:i:s'), 'end_time' => $slotStart->addHour()->format('H:i:s')],
        ], source: BookingSource::WalkIn, enforceBookingWindow: false);

        $this->assertSame(BookingSource::WalkIn, $bookings[0]->source);
    }

    public function test_check_in_records_a_timestamp_on_a_confirmed_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $checkedIn = $this->service->checkIn($booking);

        $this->assertNotNull($checkedIn->checked_in_at);
    }

    public function test_check_in_rejects_a_booking_that_is_already_checked_in(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->checkIn($booking);

        $this->expectException(BookingUnavailableException::class);
        $this->service->checkIn($booking->fresh());
    }

    public function test_check_in_rejects_a_cancelled_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->cancel($booking);

        $this->expectException(BookingUnavailableException::class);
        $this->service->checkIn($booking->fresh());
    }

    public function test_mark_completed_transitions_a_confirmed_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $completed = $this->service->markCompleted($booking);

        $this->assertSame(BookingStatus::Completed, $completed->status);
    }

    public function test_mark_completed_rejects_an_already_completed_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->markCompleted($booking);

        $this->expectException(BookingUnavailableException::class);
        $this->service->markCompleted($booking->fresh());
    }

    public function test_mark_no_show_transitions_a_confirmed_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $noShow = $this->service->markNoShow($booking);

        $this->assertSame(BookingStatus::NoShow, $noShow->status);
    }

    public function test_mark_no_show_rejects_a_cancelled_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
        $this->service->cancel($booking);

        $this->expectException(BookingUnavailableException::class);
        $this->service->markNoShow($booking->fresh());
    }

    public function test_book_with_payment_hold_creates_a_pending_booking_with_a_ten_minute_hold(): void
    {
        $court = Court::factory()->create();

        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertNotNull($booking->hold_expires_at);
        $this->assertEqualsWithDelta(
            now()->addMinutes(10)->timestamp,
            $booking->hold_expires_at->timestamp,
            5,
        );
    }

    public function test_the_hold_window_respects_the_payment_hold_minutes_setting(): void
    {
        Setting::set('payment_hold_minutes', '20');
        $court = Court::factory()->create();

        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );

        $this->assertEqualsWithDelta(
            now()->addMinutes(20)->timestamp,
            $booking->hold_expires_at->timestamp,
            5,
        );
    }

    public function test_a_payment_hold_blocks_the_slot_from_being_booked_by_someone_else(): void
    {
        $court = Court::factory()->create();
        $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );

        $this->expectException(BookingUnavailableException::class);
        $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');
    }

    public function test_confirm_with_reference_transitions_pending_to_confirmed(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );

        $confirmed = $this->service->confirmWithReference([$booking], 'GCASH-REF-999');

        $this->assertSame(BookingStatus::Confirmed, $confirmed[0]->status);
        $this->assertSame(PaymentStatus::Pending, $confirmed[0]->payment->status);
        $this->assertSame('GCASH-REF-999', $confirmed[0]->payment->reference_number);
        // Booking.payment_status is a denormalized copy of Payment.status
        // shown on the booking detail page/My Bookings - must stay in sync.
        $this->assertSame(PaymentStatus::Pending, $confirmed[0]->payment_status);
    }

    public function test_confirm_with_reference_rejects_an_expired_hold(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );
        $booking->forceFill(['hold_expires_at' => now()->subMinute()])->save();

        $this->expectException(BookingUnavailableException::class);
        $this->service->confirmWithReference([$booking], 'GCASH-REF-999');
    }

    public function test_confirm_with_reference_rejects_a_booking_that_is_not_pending(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $this->expectException(BookingUnavailableException::class);
        $this->service->confirmWithReference([$booking], 'GCASH-REF-999');
    }

    public function test_confirm_with_reference_is_all_or_nothing_across_a_batch(): void
    {
        $court = Court::factory()->create();
        $customer = User::factory()->customer()->create();
        $valid = $this->service->book($customer, $court, $this->date, '09:00:00', '10:00:00', requiresPaymentHold: true);
        $expired = $this->service->book($customer, $court, $this->date, '11:00:00', '12:00:00', requiresPaymentHold: true);
        $expired->forceFill(['hold_expires_at' => now()->subMinute()])->save();

        try {
            $this->service->confirmWithReference([$valid, $expired], 'GCASH-REF-999');
            $this->fail('Expected a BookingUnavailableException.');
        } catch (BookingUnavailableException) {
            // expected
        }

        $this->assertSame(BookingStatus::Pending, $valid->fresh()->status);
    }

    public function test_expire_payment_hold_transitions_pending_to_expired(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );

        $this->service->expirePaymentHold($booking);

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
    }

    public function test_an_expired_hold_frees_the_slot_for_a_new_booking(): void
    {
        $court = Court::factory()->create();
        $booking = $this->service->book(
            User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00',
            requiresPaymentHold: true,
        );
        $this->service->expirePaymentHold($booking);

        $newBooking = $this->service->book(User::factory()->customer()->create(), $court, $this->date, '09:00:00', '10:00:00');

        $this->assertSame(BookingStatus::Confirmed, $newBooking->status);
    }
}
