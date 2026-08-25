<?php

namespace Tests\Feature\Services;

use App\Enums\CourtStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\ClosurePeriod;
use App\Models\Court;
use App\Models\CourtMaintenance;
use App\Models\Setting;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-08-20';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('default_booking_duration_minutes', '60');

        BusinessHour::create([
            'day_of_week' => CarbonImmutable::parse(self::DATE)->dayOfWeek,
            'opens_at' => '08:00:00',
            'closes_at' => '11:00:00',
            'is_closed' => false,
        ]);
    }

    public function test_generates_available_slots_within_business_hours(): void
    {
        $court = Court::factory()->create();

        $result = (new AvailabilityService)->forDate(self::DATE);

        $this->assertFalse($result['is_facility_closed']);
        $courtAvailability = collect($result['courts'])->firstWhere('court.id', $court->id);

        // 08:00-11:00 in 60-minute slots => 08-09, 09-10, 10-11.
        $this->assertCount(3, $courtAvailability->slots);
        $this->assertSame('08:00:00', $courtAvailability->slots[0]->startTime);
        $this->assertSame('11:00:00', $courtAvailability->slots[2]->endTime);
        foreach ($courtAvailability->slots as $slot) {
            $this->assertSame(SlotStatus::Available, $slot->status);
        }
    }

    public function test_confirmed_booking_marks_its_slot_booked_and_others_available(): void
    {
        $court = Court::factory()->create();
        $booking = Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => self::DATE,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $slots = $this->slotsFor($court);

        $this->assertSame(SlotStatus::Available, $slots['08:00:00']->status);
        $this->assertSame(SlotStatus::Booked, $slots['09:00:00']->status);
        $this->assertSame($booking->id, $slots['09:00:00']->bookingId);
        $this->assertSame(SlotStatus::Available, $slots['10:00:00']->status);
    }

    public function test_pending_booking_marks_slot_in_progress_with_a_hold_expiry(): void
    {
        // Owner feedback: the payment-hold countdown should be visible to
        // every viewer of the grid, not just the person actively booking -
        // AvailabilitySlot carries holdExpiresAt so the view can render a
        // live countdown for anyone looking at this slot.
        $court = Court::factory()->create();
        $booking = Booking::factory()->pending()->create([
            'court_id' => $court->id,
            'booking_date' => self::DATE,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        $booking->forceFill(['hold_expires_at' => now()->addMinutes(10)])->save();

        $slot = $this->slotsFor($court)['09:00:00'];

        $this->assertSame(SlotStatus::InProgress, $slot->status);
        $this->assertSame($booking->hold_expires_at->toIso8601String(), $slot->holdExpiresAt);
    }

    public function test_confirmed_booking_marks_slot_booked_regardless_of_payment_status(): void
    {
        // Owner feedback: reverses the earlier three-stage split - there is
        // no separate "awaiting staff approval" grid status any more. The
        // moment a booking is Confirmed (which happens as soon as the
        // customer submits a reference number/screenshot, no staff action
        // required), the slot is Booked - whether it's a walk-in that's
        // still Unpaid, an online booking with a submitted-but-unverified
        // Payment::Pending, or one staff has already verified as Paid.
        // Staff still separately tracks payment verification via the
        // booking detail page's Mark Paid action; it just no longer gates
        // this grid.
        $cases = [
            \App\Enums\PaymentStatus::Unpaid,
            \App\Enums\PaymentStatus::Pending,
            \App\Enums\PaymentStatus::Paid,
        ];

        foreach ($cases as $paymentStatus) {
            $court = Court::factory()->create();
            $booking = Booking::factory()->create([
                'court_id' => $court->id,
                'booking_date' => self::DATE,
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
            ]);
            \App\Models\Payment::factory()->create(['booking_id' => $booking->id, 'status' => $paymentStatus]);

            $slot = $this->slotsFor($court)['09:00:00'];

            $this->assertSame(SlotStatus::Booked, $slot->status, "Payment {$paymentStatus->value} should still show Booked");
            $this->assertNull($slot->holdExpiresAt);
        }
    }

    public function test_cancelled_booking_does_not_block_availability(): void
    {
        $court = Court::factory()->create();
        Booking::factory()->cancelled()->create([
            'court_id' => $court->id,
            'booking_date' => self::DATE,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $slots = $this->slotsFor($court);

        $this->assertSame(SlotStatus::Available, $slots['09:00:00']->status);
    }

    public function test_partially_overlapping_booking_still_blocks_the_slot(): void
    {
        $court = Court::factory()->create();
        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => self::DATE,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        $slots = $this->slotsFor($court);

        $this->assertSame(SlotStatus::Booked, $slots['09:00:00']->status);
        $this->assertSame(SlotStatus::Booked, $slots['10:00:00']->status);
    }

    public function test_court_maintenance_closes_only_the_overlapping_slots(): void
    {
        $court = Court::factory()->create();
        CourtMaintenance::factory()->create([
            'court_id' => $court->id,
            'starts_at' => self::DATE.' 09:00:00',
            'ends_at' => self::DATE.' 10:00:00',
        ]);

        $slots = $this->slotsFor($court);

        $this->assertSame(SlotStatus::Available, $slots['08:00:00']->status);
        $this->assertSame(SlotStatus::Closed, $slots['09:00:00']->status);
        $this->assertSame(SlotStatus::Available, $slots['10:00:00']->status);
    }

    public function test_closure_period_closes_every_court(): void
    {
        $courtA = Court::factory()->create();
        $courtB = Court::factory()->create();
        ClosurePeriod::factory()->create([
            'title' => 'Facility Closed',
            'starts_at' => self::DATE.' 00:00:00',
            'ends_at' => self::DATE.' 23:59:59',
        ]);

        $result = (new AvailabilityService)->forDate(self::DATE);

        foreach ($result['courts'] as $courtAvailability) {
            foreach ($courtAvailability->slots as $slot) {
                $this->assertSame(SlotStatus::Closed, $slot->status);
            }
        }
    }

    public function test_inactive_court_is_entirely_closed_regardless_of_bookings(): void
    {
        $court = Court::factory()->create(['status' => CourtStatus::Maintenance]);

        $slots = $this->slotsFor($court);

        foreach ($slots as $slot) {
            $this->assertSame(SlotStatus::Closed, $slot->status);
        }
    }

    public function test_facility_closed_day_returns_no_slots(): void
    {
        BusinessHour::where('day_of_week', CarbonImmutable::parse(self::DATE)->dayOfWeek)
            ->update(['is_closed' => true]);

        Court::factory()->create();

        $result = (new AvailabilityService)->forDate(self::DATE);

        $this->assertTrue($result['is_facility_closed']);
        $this->assertSame([], $result['courts'][0]->slots);
    }

    public function test_business_hours_crossing_midnight_spill_into_the_next_day(): void
    {
        // Yesterday's hours (opens 20:00, closes 01:00 - crosses midnight)
        // should contribute an early-morning spillover to *today's* slots,
        // on top of today's own 08:00-11:00 window from setUp().
        $yesterday = CarbonImmutable::parse(self::DATE)->subDay();
        BusinessHour::create([
            'day_of_week' => $yesterday->dayOfWeek,
            'opens_at' => '20:00:00',
            'closes_at' => '01:00:00',
            'is_closed' => false,
        ]);

        $court = Court::factory()->create();

        $result = (new AvailabilityService)->forDate(self::DATE);

        $this->assertFalse($result['is_facility_closed']);
        $courtAvailability = collect($result['courts'])->firstWhere('court.id', $court->id);
        $times = collect($courtAvailability->slots)->map(fn ($slot) => [$slot->startTime, $slot->endTime])->all();

        // Spillover (00:00-01:00) comes first, then today's own 08:00-11:00.
        $this->assertSame([
            ['00:00:00', '01:00:00'],
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
        ], $times);
    }

    public function test_a_spillover_slot_can_be_booked_and_blocks_re_booking(): void
    {
        $yesterday = CarbonImmutable::parse(self::DATE)->subDay();
        BusinessHour::create([
            'day_of_week' => $yesterday->dayOfWeek,
            'opens_at' => '20:00:00',
            'closes_at' => '01:00:00',
            'is_closed' => false,
        ]);

        $court = Court::factory()->create();
        Booking::factory()->create([
            'court_id' => $court->id,
            'booking_date' => self::DATE,
            'start_time' => '00:00:00',
            'end_time' => '01:00:00',
        ]);

        $slots = $this->slotsFor($court);

        $this->assertSame(SlotStatus::Booked, $slots['00:00:00']->status);
    }

    public function test_business_day_with_closing_equal_to_opening_runs_a_full_24_hours(): void
    {
        BusinessHour::where('day_of_week', CarbonImmutable::parse(self::DATE)->dayOfWeek)
            ->update(['opens_at' => '06:00:00', 'closes_at' => '06:00:00']);

        $yesterday = CarbonImmutable::parse(self::DATE)->subDay();
        BusinessHour::create([
            'day_of_week' => $yesterday->dayOfWeek,
            'opens_at' => '06:00:00',
            'closes_at' => '06:00:00',
            'is_closed' => false,
        ]);

        $court = Court::factory()->create();

        $result = (new AvailabilityService)->forDate(self::DATE);
        $courtAvailability = collect($result['courts'])->firstWhere('court.id', $court->id);

        // 00:00-06:00 spillover from "yesterday" (6 hourly slots) plus
        // 06:00-23:00 today (17 hourly slots) plus one final short slot
        // 23:00-23:59:59 (DEC-018's fix for the last-hour-before-midnight
        // gap) = 24 slots total.
        $slots = $courtAvailability->slots;
        $this->assertCount(24, $slots);
        $this->assertSame('00:00:00', $slots[0]->startTime);
        $this->assertSame('23:00:00', end($slots)->startTime);
        $this->assertSame('23:59:59', end($slots)->endTime);
    }

    /**
     * DEC-018: the real-world scenario the facility owner actually hit -
     * every day open 6am to 2am the next morning (this project's actual
     * seeded business hours), and the 11pm-midnight hour specifically was
     * never bookable online at all before this fix.
     */
    public function test_the_hour_before_midnight_is_bookable_when_business_hours_cross_midnight(): void
    {
        BusinessHour::where('day_of_week', CarbonImmutable::parse(self::DATE)->dayOfWeek)
            ->update(['opens_at' => '06:00:00', 'closes_at' => '02:00:00']);

        $court = Court::factory()->create();

        $slots = $this->slotsFor($court);

        $this->assertArrayHasKey('23:00:00', $slots);
        $this->assertSame('23:59:59', $slots['23:00:00']->endTime);
        $this->assertSame(SlotStatus::Available, $slots['23:00:00']->status);
    }

    /**
     * The specific risk that made a naive fix unsafe in the first place:
     * confirms double-booking protection genuinely still works for this
     * slot, not just that it's generated - goes through the real
     * BookingService::book() write path, not just AvailabilityService.
     */
    public function test_the_hour_before_midnight_still_blocks_a_second_booking(): void
    {
        Setting::set('default_booking_duration_minutes', '60');
        Setting::set('min_booking_notice_minutes', '0');
        Setting::set('max_advance_booking_days', '30');
        BusinessHour::where('day_of_week', CarbonImmutable::parse(self::DATE)->dayOfWeek)
            ->update(['opens_at' => '06:00:00', 'closes_at' => '02:00:00']);

        $court = Court::factory()->create();
        $user = \App\Models\User::factory()->customer()->create();
        $bookingService = app(\App\Services\BookingService::class);

        $bookingService->book($user, $court, self::DATE, '23:00:00', '23:59:59');

        $this->expectException(\App\Exceptions\BookingUnavailableException::class);
        $bookingService->book($user, $court, self::DATE, '23:00:00', '23:59:59');
    }

    public function test_missing_business_hour_record_is_treated_as_closed(): void
    {
        BusinessHour::query()->delete();
        Court::factory()->create();

        $result = (new AvailabilityService)->forDate(self::DATE);

        $this->assertTrue($result['is_facility_closed']);
    }

    public function test_query_count_does_not_grow_with_courts_or_bookings(): void
    {
        Court::factory()->count(5)->create()->each(function (Court $court) {
            Booking::factory()->count(3)->create(['court_id' => $court->id, 'booking_date' => self::DATE]);
        });

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        (new AvailabilityService)->forDate(self::DATE);

        // Fixed number of queries: today's business hour, yesterday's
        // business hour (for midnight spillover), courts, bookings,
        // maintenance, closures, open play sessions — must not scale with
        // court/booking count.
        $this->assertLessThanOrEqual(8, $queryCount);
    }

    /**
     * @return array<string, \App\Services\AvailabilitySlot>
     */
    private function slotsFor(Court $court): array
    {
        $result = (new AvailabilityService)->forDate(self::DATE);
        $courtAvailability = collect($result['courts'])->firstWhere('court.id', $court->id);

        return collect($courtAvailability->slots)->keyBy('startTime')->all();
    }
}
