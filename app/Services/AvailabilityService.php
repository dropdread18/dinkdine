<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\ClosurePeriod;
use App\Models\Court;
use App\Models\CourtMaintenance;
use App\Models\OpenPlaySession;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Computes court availability for a single date by combining business
 * hours, existing bookings, court maintenance, and facility closures.
 * Requirements.md §9-10, §47. Nothing here writes data or prevents a
 * booking from being made — that's BookingService (Phase 5).
 */
class AvailabilityService
{
    /**
     * @param  int|null  $excludeBookingId  Ignore this booking when computing conflicts - used when
     *                                      rescheduling, so a booking's own current slot doesn't block it.
     * @return array{date: string, is_facility_closed: bool, courts: CourtAvailability[]}
     */
    public function forDate(string $date, ?int $excludeBookingId = null): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $duration = $this->slotDurationMinutes();

        $todayHour = BusinessHour::where('day_of_week', $day->dayOfWeek)->first();
        $yesterdayHour = BusinessHour::where('day_of_week', $day->subDay()->dayOfWeek)->first();

        // A business day whose closing time crosses midnight (e.g. opens
        // 06:00, closes 02:00) is split across two calendar dates: today's
        // own slots run from opens_at through day-end, and the post-midnight
        // portion (00:00 through closes_at) belongs to *tomorrow's* forDate()
        // call - spilloverSlots() below pulls that in from yesterday's row.
        // Both halves stay ordinary same-day time ranges, so no slot ever
        // needs an ambiguous "ends at 00:00:00" boundary.
        $slotTimes = [
            ...$this->spilloverSlotTimes($yesterdayHour, $duration),
            ...$this->businessHourSlotTimes($todayHour, $duration),
        ];

        $facilityClosed = empty($slotTimes);

        $courts = Court::orderBy('sort_order')->orderBy('court_number')->get();

        $bookingsByCourt = Booking::query()
            ->whereDate('booking_date', $day)
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
            ->when($excludeBookingId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->get()
            ->groupBy('court_id');

        $maintenanceByCourt = CourtMaintenance::query()
            ->where('starts_at', '<=', $day->endOfDay())
            ->where('ends_at', '>=', $day->startOfDay())
            ->get()
            ->groupBy('court_id');

        $closurePeriods = ClosurePeriod::query()
            ->where('starts_at', '<=', $day->endOfDay())
            ->where('ends_at', '>=', $day->startOfDay())
            ->get();

        $openPlayByCourt = OpenPlaySession::query()
            ->whereDate('session_date', $day)
            ->get()
            ->groupBy('court_id');

        $courtAvailabilities = $courts->map(function (Court $court) use ($slotTimes, $day, $bookingsByCourt, $maintenanceByCourt, $closurePeriods, $openPlayByCourt) {
            $slots = array_map(
                fn (array $range) => $this->resolveSlot(
                    $court,
                    $day,
                    $range[0],
                    $range[1],
                    $bookingsByCourt->get($court->id, new Collection),
                    $maintenanceByCourt->get($court->id, new Collection),
                    $closurePeriods,
                    $openPlayByCourt->get($court->id, new Collection),
                ),
                $slotTimes,
            );

            return new CourtAvailability($court, $slots);
        })->all();

        return [
            'date' => $day->toDateString(),
            'is_facility_closed' => $facilityClosed,
            'courts' => $courtAvailabilities,
        ];
    }

    private function resolveSlot(
        Court $court,
        CarbonImmutable $day,
        string $startTime,
        string $endTime,
        Collection $courtBookings,
        Collection $courtMaintenance,
        Collection $closurePeriods,
        Collection $courtOpenPlay,
    ): AvailabilitySlot {
        if (! $court->isBookable()) {
            return new AvailabilitySlot($startTime, $endTime, SlotStatus::Closed);
        }

        if ($this->anyPeriodCoversSlot($closurePeriods, $day, $startTime, $endTime)) {
            return new AvailabilitySlot($startTime, $endTime, SlotStatus::Closed);
        }

        if ($this->anyPeriodCoversSlot($courtMaintenance, $day, $startTime, $endTime)) {
            return new AvailabilitySlot($startTime, $endTime, SlotStatus::Closed);
        }

        // Open Play blocks the slot for regular booking but shows its own
        // distinct status on the grid rather than a plain Closed - no
        // signup or payment happens on this site (organizers run that
        // off-platform), this just marks the court as taken.
        foreach ($courtOpenPlay as $session) {
            if ($this->timeRangesOverlap($startTime, $endTime, $session->start_time, $session->end_time)) {
                return new AvailabilitySlot(
                    $startTime,
                    $endTime,
                    SlotStatus::OpenPlay,
                    // Grouped by date+time window, not batch_id - the same
                    // event on two courts should read as one color whether
                    // it was scheduled in one multi-court submission or as
                    // two separate single-court ones (batch_id only ever
                    // captures the former, and is null for any session
                    // that predates the column entirely).
                    openPlayGroupKey: $day->toDateString().'|'.$session->start_time.'|'.$session->end_time,
                    openPlayLink: $session->registration_link,
                    openPlayStartTime: $session->start_time,
                    openPlayEndTime: $session->end_time,
                );
            }
        }

        foreach ($courtBookings as $booking) {
            if ($this->timeRangesOverlap($startTime, $endTime, $booking->start_time, $booking->end_time)) {
                // Owner feedback (reverses the earlier three-stage split):
                // no approval step before a slot shows Booked - the moment
                // a booking is Confirmed (BookingService::confirmWithReference()
                // runs as soon as the customer submits a reference number
                // or screenshot, no staff action needed), the slot is
                // Booked. Staff still separately verifies the *payment*
                // itself via Mark Paid on the booking detail page for their
                // own bookkeeping, but that no longer gates what this grid
                // shows. Only a still-Pending booking (the hold countdown
                // running, nothing submitted yet) is InProgress.
                $isInProgress = $booking->status === BookingStatus::Pending;

                return new AvailabilitySlot(
                    $startTime,
                    $endTime,
                    $isInProgress ? SlotStatus::InProgress : SlotStatus::Booked,
                    $booking->id,
                    $isInProgress ? $booking->hold_expires_at?->toIso8601String() : null,
                );
            }
        }

        // A slot that has already fully elapsed today can never be booked -
        // by anyone, including a walk-in (which otherwise bypasses the
        // online min-notice window entirely). Deliberately checks the END
        // time, not the start time: the slot currently in progress (started
        // but not yet finished) must stay Available, since "book the court
        // right now" is the whole point of walk-in bookings - tested via
        // WalkInBookingTest's soonSlot() helper, which relies on exactly
        // this. Only applies to today; a future date's slots always end
        // after now, so this is a no-op for every other date.
        if ($day->isToday() && $day->setTimeFromTimeString($endTime)->lt(CarbonImmutable::now())) {
            return new AvailabilitySlot($startTime, $endTime, SlotStatus::Closed);
        }

        return new AvailabilitySlot($startTime, $endTime, SlotStatus::Available);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function businessHourSlotTimes(?BusinessHour $hour, int $durationMinutes): array
    {
        if (! $hour || $hour->is_closed) {
            return [];
        }

        // closes_at <= opens_at means this business day runs past midnight
        // (e.g. opens 06:00, closes 02:00) - the after-midnight portion is
        // generated separately for tomorrow's date by spilloverSlotTimes().
        // Today's own slots stop at day-end.
        $closesToday = $hour->closes_at > $hour->opens_at ? $hour->closes_at : '23:59:59';

        return $this->generateTimeSlots($hour->opens_at, $closesToday, $durationMinutes);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function spilloverSlotTimes(?BusinessHour $yesterday, int $durationMinutes): array
    {
        if (! $yesterday || $yesterday->is_closed || $yesterday->closes_at > $yesterday->opens_at) {
            return [];
        }

        return $this->generateTimeSlots('00:00:00', $yesterday->closes_at, $durationMinutes);
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function generateTimeSlots(string $opensAt, string $closesAt, int $durationMinutes): array
    {
        $slots = [];
        $slotStart = CarbonImmutable::createFromFormat('H:i:s', $opensAt);
        $closes = CarbonImmutable::createFromFormat('H:i:s', $closesAt);

        while (true) {
            $slotEnd = $slotStart->addMinutes($durationMinutes);

            if ($slotEnd->gt($closes)) {
                break;
            }

            $slots[] = [$slotStart->format('H:i:s'), $slotEnd->format('H:i:s')];
            $slotStart = $slotEnd;
        }

        // '23:59:59' is this class's own internal marker for "crosses
        // midnight" (see businessHourSlotTimes()) - never a value an admin
        // actually configures. The loop above always stops one slot short
        // of midnight: a full-duration slot starting at e.g. 23:00 would
        // end at 00:00:00 the NEXT day, and every time comparison in this
        // app (timeRangesOverlap() below, BookingService::book()'s DB
        // conflict query) treats times as plain strings/TIME values within
        // a single day - '00:00:00' always sorts as the smallest time, not
        // the largest, which would silently break double-booking
        // protection for that slot. Capping this one final slot at
        // 23:59:59 instead of a full $durationMinutes keeps its end_time
        // safely within the same calendar day (a few seconds short of a
        // full hour) rather than ambiguous with the next day's midnight -
        // this is what makes the last hour before midnight bookable at all.
        if ($closesAt === '23:59:59' && $slotStart->lt($closes)) {
            $slots[] = [$slotStart->format('H:i:s'), $closes->format('H:i:s')];
        }

        return $slots;
    }

    /**
     * @param  Collection<int, ClosurePeriod|CourtMaintenance>  $periods
     */
    private function anyPeriodCoversSlot(Collection $periods, CarbonImmutable $day, string $startTime, string $endTime): bool
    {
        $slotStart = $day->setTimeFromTimeString($startTime);
        $slotEnd = $day->setTimeFromTimeString($endTime);

        foreach ($periods as $period) {
            if ($slotStart->lt($period->ends_at) && $period->starts_at->lt($slotEnd)) {
                return true;
            }
        }

        return false;
    }

    private function timeRangesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return $startA < $endB && $startB < $endA;
    }

    private function slotDurationMinutes(): int
    {
        return (int) (Setting::get('default_booking_duration_minutes') ?? 60);
    }
}
