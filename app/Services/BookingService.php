<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SlotStatus;
use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The only place a booking should ever be created from. Re-validates
 * availability, then re-checks for conflicts a second time inside a
 * locked transaction immediately before writing (Requirements.md §14).
 *
 * The transactional check relies on MySQL/MariaDB's default REPEATABLE
 * READ isolation: a SELECT ... FOR UPDATE over an indexed range (our
 * bookings_availability_index) takes InnoDB gap locks even when zero
 * rows currently match, which blocks a second concurrent transaction
 * from inserting into that same range until the first commits. This is
 * the standard way to prevent double-booking without a separate locking
 * primitive - but it is a MySQL/InnoDB behavior, not something SQLite
 * (used in tests) replicates. Tests can only prove the sequential case
 * (second attempt rejected after the first commits), not true
 * simultaneous-request safety - that's only guaranteed on production MySQL.
 */
class BookingService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @throws BookingUnavailableException
     */
    public function book(
        User $user,
        Court $court,
        string $date,
        string $startTime,
        string $endTime,
        ?string $notes = null,
        BookingSource $source = BookingSource::Online,
        bool $enforceBookingWindow = true,
    ): Booking {
        if ($enforceBookingWindow) {
            $this->assertWithinBookingWindow($date, $startTime);
        }
        $this->assertSlotIsAvailable($court, $date, $startTime, $endTime);

        return DB::transaction(function () use ($user, $court, $date, $startTime, $endTime, $notes, $source) {
            $conflict = Booking::query()
                ->where('court_id', $court->id)
                ->where('booking_date', $date)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new BookingUnavailableException(
                    'Sorry, this court was just booked by another customer. Please select another available time.'
                );
            }

            return Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $this->calculatePrice($court, $startTime, $endTime),
                'status' => BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Unpaid,
                'source' => $source,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Moves an existing booking to a different court/date/time, re-validated
     * exactly like a fresh booking (Requirements.md §21) - the booking's own
     * current slot is excluded from the conflict check so it doesn't block
     * itself.
     *
     * @throws BookingUnavailableException
     */
    public function reschedule(Booking $booking, Court $court, string $date, string $startTime, string $endTime, bool $enforcePolicy = false): Booking
    {
        if ($enforcePolicy) {
            $this->assertEligibleForCustomerAction($booking, 'rescheduled');
        }

        $this->assertWithinBookingWindow($date, $startTime);
        $this->assertSlotIsAvailable($court, $date, $startTime, $endTime, excludeBookingId: $booking->id);

        return DB::transaction(function () use ($booking, $court, $date, $startTime, $endTime) {
            $conflict = Booking::query()
                ->where('court_id', $court->id)
                ->where('booking_date', $date)
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->where('id', '!=', $booking->id)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new BookingUnavailableException(
                    'Sorry, that time is no longer available. Please select another available time.'
                );
            }

            $booking->update([
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $this->calculatePrice($court, $startTime, $endTime),
            ]);

            return $booking->fresh();
        });
    }

    /**
     * @param  bool  $enforcePolicy  Staff/admin bypass the customer eligibility
     *                               window (default false here - opposite default
     *                               from reschedule()/book() since staff cancelling
     *                               is the far more common caller today).
     *
     * @throws BookingUnavailableException
     */
    public function cancel(Booking $booking, ?string $reason = null, bool $enforcePolicy = false): Booking
    {
        if ($booking->status === BookingStatus::Cancelled) {
            throw new BookingUnavailableException('This booking is already cancelled.');
        }

        if ($enforcePolicy) {
            $this->assertEligibleForCustomerAction($booking, 'cancelled');
        }

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'notes' => trim(($booking->notes ? $booking->notes."\n" : '').'Cancelled: '.($reason ?: 'No reason given')),
        ]);

        return $booking->fresh();
    }

    /**
     * Whether a customer (not staff/admin) may cancel or reschedule this
     * booking right now: it must still be Pending/Confirmed, and at least
     * `cancellation_deadline_hours` (Requirements.md §20, default 4) before
     * its start time. Public so views can hide the Cancel/Reschedule
     * buttons instead of only failing after the fact.
     */
    public function isEligibleForCustomerAction(Booking $booking): bool
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            return false;
        }

        $deadlineHours = (int) (Setting::get('cancellation_deadline_hours') ?? 4);
        $bookingStart = CarbonImmutable::parse($booking->booking_date->toDateString().' '.$booking->start_time);

        return CarbonImmutable::now()->addHours($deadlineHours)->lte($bookingStart);
    }

    /**
     * @throws BookingUnavailableException
     */
    private function assertEligibleForCustomerAction(Booking $booking, string $action): void
    {
        if ($this->isEligibleForCustomerAction($booking)) {
            return;
        }

        $deadlineHours = (int) (Setting::get('cancellation_deadline_hours') ?? 4);

        throw new BookingUnavailableException(
            "This booking can no longer be {$action} online - it's either already finished, cancelled, or within the {$deadlineHours}-hour cutoff. Please contact the facility directly."
        );
    }

    private function assertWithinBookingWindow(string $date, string $startTime): void
    {
        $slotStart = CarbonImmutable::parse("{$date} {$startTime}");
        $minNotice = (int) (Setting::get('min_booking_notice_minutes') ?? 30);
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        if ($slotStart->lt(CarbonImmutable::now()->addMinutes($minNotice))) {
            throw new BookingUnavailableException('This time is too soon to book. Please choose a later time.');
        }

        if ($slotStart->gt(CarbonImmutable::now()->addDays($maxAdvanceDays))) {
            throw new BookingUnavailableException('This date is too far in advance to book yet.');
        }
    }

    private function assertSlotIsAvailable(Court $court, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): void
    {
        $day = $this->availability->forDate($date, $excludeBookingId);

        /** @var CourtAvailability|null $courtAvailability */
        $courtAvailability = collect($day['courts'])->first(fn (CourtAvailability $ca) => $ca->court->is($court));

        if (! $courtAvailability) {
            throw new BookingUnavailableException('This court is not available for booking.');
        }

        $slot = collect($courtAvailability->slots)->first(
            fn (AvailabilitySlot $slot) => $slot->startTime === $startTime && $slot->endTime === $endTime
        );

        if (! $slot || $slot->status !== SlotStatus::Available) {
            throw new BookingUnavailableException(
                'Sorry, that time is no longer available. Please select another available time.'
            );
        }
    }

    private function calculatePrice(Court $court, string $startTime, string $endTime): float
    {
        $start = CarbonImmutable::createFromFormat('H:i:s', $startTime);
        $end = CarbonImmutable::createFromFormat('H:i:s', $endTime);
        $minutes = abs($end->getTimestamp() - $start->getTimestamp()) / 60;

        return round((float) $court->hourly_rate * ($minutes / 60), 2);
    }
}
