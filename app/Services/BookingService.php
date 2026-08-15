<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\SlotStatus;
use App\Exceptions\BookingUnavailableException;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRescheduled;
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
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    /**
     * @param  bool  $requiresPaymentHold  Guest-checkout payment-hold flow
     *                                     (feedback session, post-launch):
     *                                     instead of going straight to
     *                                     Confirmed, the booking is created
     *                                     Pending with a 10-minute
     *                                     hold_expires_at. It still blocks
     *                                     the slot exactly like a Confirmed
     *                                     booking (assertSlotIsAvailable/the
     *                                     conflict check below both already
     *                                     treat Pending as blocking - no
     *                                     change needed there), but no
     *                                     confirmation email fires yet;
     *                                     that happens only once
     *                                     confirmWithReference() succeeds,
     *                                     or the hold silently expires via
     *                                     the bookings:expire-payment-holds
     *                                     scheduled command.
     *
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
        bool $requiresPaymentHold = false,
    ): Booking {
        if ($enforceBookingWindow) {
            $this->assertWithinBookingWindow($date, $startTime);
        }
        $this->assertSlotIsAvailable($court, $date, $startTime, $endTime);

        return DB::transaction(function () use ($user, $court, $date, $startTime, $endTime, $notes, $source, $requiresPaymentHold) {
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

            $booking = Booking::create([
                'user_id' => $user->id,
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $this->pricing->calculate($court, $startTime, $endTime),
                'status' => $requiresPaymentHold ? BookingStatus::Pending : BookingStatus::Confirmed,
                'payment_status' => PaymentStatus::Unpaid,
                'source' => $source,
                'notes' => $notes,
            ]);

            if ($requiresPaymentHold) {
                $booking->forceFill(['hold_expires_at' => now()->addMinutes(10)])->save();
            }

            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $booking->price,
                'status' => PaymentStatus::Unpaid,
            ]);

            if (! $requiresPaymentHold) {
                // afterCommit(), not a direct notify() call here: book() can
                // run nested inside bookMany()'s outer transaction (as a
                // savepoint). Sending the email immediately would fire it
                // even if a later slot in the same batch fails and rolls
                // this one back too. afterCommit() always waits for the
                // OUTERMOST transaction, whichever call started it, and
                // fires immediately if there's no transaction in progress
                // at all.
                DB::afterCommit(fn () => $booking->user->notify(new BookingConfirmed($booking)));
            }

            return $booking;
        });
    }

    /**
     * Books several slots as one all-or-nothing action (DEC-004: a customer
     * selects any combination of slots - not necessarily consecutive or on
     * the same court - and confirms them together). Wraps everything in one
     * outer transaction; each slot still goes through book()'s full
     * validation and its own inner transaction (Laravel nests these as
     * savepoints), so if any slot fails, every booking in the batch -
     * including ones that "succeeded" earlier in the loop - rolls back.
     * The exception is re-thrown with the specific slot identified, so the
     * caller can tell the customer exactly which selection to fix.
     *
     * @param  array<int, array{court: Court, date: string, start_time: string, end_time: string}>  $slots
     * @return Booking[]
     *
     * @throws BookingUnavailableException
     */
    public function bookMany(
        User $user,
        array $slots,
        ?string $notes = null,
        BookingSource $source = BookingSource::Online,
        bool $enforceBookingWindow = true,
        bool $requiresPaymentHold = false,
    ): array {
        if (empty($slots)) {
            throw new BookingUnavailableException('Select at least one time slot.');
        }

        return DB::transaction(function () use ($user, $slots, $notes, $source, $enforceBookingWindow, $requiresPaymentHold) {
            $bookings = [];

            foreach ($slots as $slot) {
                try {
                    $bookings[] = $this->book(
                        user: $user,
                        court: $slot['court'],
                        date: $slot['date'],
                        startTime: $slot['start_time'],
                        endTime: $slot['end_time'],
                        notes: $notes,
                        source: $source,
                        enforceBookingWindow: $enforceBookingWindow,
                        requiresPaymentHold: $requiresPaymentHold,
                    );
                } catch (BookingUnavailableException $e) {
                    $when = CarbonImmutable::parse($slot['date'].' '.$slot['start_time'])->format('M j \a\t g:i A');

                    throw new BookingUnavailableException("{$slot['court']->name}, {$when}: {$e->getMessage()}");
                }
            }

            return $bookings;
        });
    }

    /**
     * Second half of the guest payment-hold flow: the guest reports the
     * reference number they were given after paying (per
     * Setting::get('payment_instructions')). Moves every booking in this
     * hold from Pending to Confirmed and records the reference number on
     * each one's Payment (status Unpaid -> Pending - claimed-paid, not yet
     * staff-verified; staff verifies it in person at arrival via the
     * existing Check-in screen and marks it Paid through the existing
     * PaymentService::markPaid(), same as any other manual payment
     * confirmation - no new payment-verification code needed there).
     *
     * All-or-nothing, like bookMany(): if any booking in the batch already
     * expired, the whole submission fails rather than partially confirming
     * some slots and not others - the guest paid for the batch as one
     * transaction, so it's confirmed as one transaction. Checked directly
     * against hold_expires_at rather than trusting status alone, since the
     * bookings:expire-payment-holds sweep may not have run yet (it's on a
     * schedule, not instant) even though the window has technically passed.
     *
     * @param  Booking[]  $bookings
     * @return Booking[]
     *
     * @throws BookingUnavailableException
     */
    public function confirmWithReference(array $bookings, string $referenceNumber): array
    {
        return DB::transaction(function () use ($bookings, $referenceNumber) {
            $confirmed = [];

            foreach ($bookings as $booking) {
                $fresh = $booking->fresh();

                if ($fresh->status !== BookingStatus::Pending || ! $fresh->hold_expires_at) {
                    throw new BookingUnavailableException('This booking is no longer awaiting payment.');
                }

                if ($fresh->hold_expires_at->isPast()) {
                    throw new BookingUnavailableException('The 10-minute payment window has expired. Please select your slots again.');
                }

                // Booking.payment_status is a denormalized copy of
                // Payment.status shown everywhere else in the app (booking
                // detail, My Bookings, etc) - the two must change together,
                // same as every transition in PaymentService, or they drift.
                $fresh->update(['status' => BookingStatus::Confirmed, 'payment_status' => PaymentStatus::Pending]);
                $fresh->payment?->update([
                    'status' => PaymentStatus::Pending,
                    'reference_number' => $referenceNumber,
                ]);

                $confirmed[] = $fresh->fresh(['court', 'user']);
            }

            foreach ($confirmed as $booking) {
                DB::afterCommit(fn () => $booking->user->notify(new BookingConfirmed($booking)));
            }

            return $confirmed;
        });
    }

    /**
     * Requirements-driven (feedback session): a guest hold that never got a
     * reference number within its 10-minute window should silently release
     * the slot, not stay Pending forever blocking it. Called by the
     * bookings:expire-payment-holds scheduled command, not directly by any
     * controller/component.
     */
    public function expirePaymentHold(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::Expired]);
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

            $oldCourtName = $booking->court->name;
            $oldDate = $booking->booking_date->toDateString();
            $oldStartTime = $booking->start_time;
            $oldEndTime = $booking->end_time;

            $booking->update([
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $this->pricing->calculate($court, $startTime, $endTime),
            ]);

            // Keep the payment amount in sync with the new price - but only
            // while nothing has actually been paid yet. Once money has
            // moved, changing the recorded amount out from under it would
            // misrepresent what was actually collected; a price difference
            // after payment needs a human to reconcile, not a silent update.
            if ($booking->payment && $booking->payment->status === PaymentStatus::Unpaid) {
                $booking->payment->update(['amount' => $booking->price]);
            }

            $fresh = $booking->fresh(['court', 'user']);

            DB::afterCommit(fn () => $fresh->user->notify(
                new BookingRescheduled($fresh, $oldCourtName, $oldDate, $oldStartTime, $oldEndTime)
            ));

            return $fresh;
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

        $fresh = $booking->fresh(['court', 'user']);

        DB::afterCommit(fn () => $fresh->user->notify(new BookingCancelled($fresh)));

        return $fresh;
    }

    /**
     * Requirements.md §12: staff marks a customer as arrived. Purely
     * informational - unlike status, it doesn't gate availability or
     * anything else, so it's a plain timestamp column set via forceFill(),
     * same pattern as the reminder-sent columns, not mass-assignable.
     *
     * @throws BookingUnavailableException
     */
    public function checkIn(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            throw new BookingUnavailableException('Only a pending or confirmed booking can be checked in.');
        }

        if ($booking->checked_in_at) {
            throw new BookingUnavailableException('This booking is already checked in.');
        }

        $booking->forceFill(['checked_in_at' => now()])->save();

        return $booking->fresh();
    }

    /**
     * @throws BookingUnavailableException
     */
    public function markCompleted(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            throw new BookingUnavailableException('Only a pending or confirmed booking can be marked completed.');
        }

        $booking->update(['status' => BookingStatus::Completed]);

        return $booking->fresh();
    }

    /**
     * @throws BookingUnavailableException
     */
    public function markNoShow(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            throw new BookingUnavailableException('Only a pending or confirmed booking can be marked no-show.');
        }

        $booking->update(['status' => BookingStatus::NoShow]);

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

}
