<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SlotStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\ClosurePeriod;
use App\Models\Court;
use App\Models\CourtMaintenance;
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

        $businessHour = BusinessHour::where('day_of_week', $day->dayOfWeek)->first();
        $facilityClosed = ! $businessHour || $businessHour->is_closed;

        $slotTimes = $facilityClosed
            ? []
            : $this->generateTimeSlots($businessHour->opens_at, $businessHour->closes_at, $this->slotDurationMinutes());

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

        $courtAvailabilities = $courts->map(function (Court $court) use ($slotTimes, $day, $bookingsByCourt, $maintenanceByCourt, $closurePeriods) {
            $slots = array_map(
                fn (array $range) => $this->resolveSlot(
                    $court,
                    $day,
                    $range[0],
                    $range[1],
                    $bookingsByCourt->get($court->id, new Collection),
                    $maintenanceByCourt->get($court->id, new Collection),
                    $closurePeriods,
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

        foreach ($courtBookings as $booking) {
            if ($this->timeRangesOverlap($startTime, $endTime, $booking->start_time, $booking->end_time)) {
                $status = $booking->status === BookingStatus::Confirmed ? SlotStatus::Booked : SlotStatus::Pending;

                return new AvailabilitySlot($startTime, $endTime, $status, $booking->id);
            }
        }

        return new AvailabilitySlot($startTime, $endTime, SlotStatus::Available);
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
