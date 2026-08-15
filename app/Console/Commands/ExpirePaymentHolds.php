<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Feedback session, guest-checkout payment-hold flow: a Pending booking
 * whose 10-minute hold_expires_at has passed without a payment reference
 * number releases the slot back to Available. Runs every minute (not
 * everyFifteenMinutes like bookings:send-reminders) - a 10-minute window
 * needs much tighter granularity, or most of the hold time would already
 * be gone before the sweep even ran once.
 */
#[Signature('bookings:expire-payment-holds')]
#[Description('Expire guest bookings whose 10-minute payment hold has lapsed without a reference number.')]
class ExpirePaymentHolds extends Command
{
    public function handle(BookingService $bookingService): void
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::Pending)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->get();

        foreach ($expired as $booking) {
            $bookingService->expirePaymentHold($booking);
        }

        $this->info("Expired {$expired->count()} payment hold(s).");
    }
}
