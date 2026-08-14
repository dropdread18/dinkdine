<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Requirements.md §32. Idempotent and catch-up-safe by design: a booking's
 * reminder_24h_sent_at / reminder_1h_sent_at only gets set once a reminder
 * actually goes out, so re-running (or a missed scheduler tick) never
 * double-sends or skips a booking - it just sends late.
 */
#[Signature('bookings:send-reminders')]
#[Description('Send 24-hour and 1-hour reminder emails for upcoming confirmed bookings.')]
class SendBookingReminders extends Command
{
    public function handle(): void
    {
        $now = CarbonImmutable::now();

        $sent24h = $this->sendDue($now, hoursBefore: 24, column: 'reminder_24h_sent_at');
        $sent1h = $this->sendDue($now, hoursBefore: 1, column: 'reminder_1h_sent_at');

        $this->info("Sent {$sent24h} 24-hour and {$sent1h} 1-hour reminder(s).");
    }

    private function sendDue(CarbonImmutable $now, int $hoursBefore, string $column): int
    {
        /** @var Collection<int, Booking> $candidates */
        $candidates = Booking::query()
            ->with(['court', 'user'])
            ->where('status', BookingStatus::Confirmed)
            ->whereNull($column)
            ->whereBetween('booking_date', [$now->toDateString(), $now->addDays(2)->toDateString()])
            ->get();

        $due = $candidates->filter(function (Booking $booking) use ($now, $hoursBefore) {
            $start = CarbonImmutable::parse($booking->booking_date->toDateString().' '.$booking->start_time);

            // "Crossed into the window and hasn't started yet" - not a tight
            // window, so a missed/delayed scheduler run still catches it.
            return $start->gt($now) && $now->gte($start->subHours($hoursBefore));
        });

        foreach ($due as $booking) {
            $booking->user->notify(new BookingReminder($booking, $hoursBefore));
            $booking->forceFill([$column => $now])->save();
        }

        return $due->count();
    }
}
