<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class BookingRescheduled extends Notification
{
    /**
     * $old* capture the booking's state before the reschedule was applied -
     * by the time this fires, $booking already reflects the new slot.
     */
    public function __construct(
        public readonly Booking $booking,
        public readonly string $oldCourtName,
        public readonly string $oldDate,
        public readonly string $oldStartTime,
        public readonly string $oldEndTime,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;

        return (new MailMessage)
            ->subject("Booking Rescheduled - PB-{$booking->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your booking PB-{$booking->id} has been rescheduled.")
            ->line('Previously: '.$this->oldCourtName.', '.Carbon::parse($this->oldDate)->format('F j, Y').', '.
                Carbon::createFromFormat('H:i:s', $this->oldStartTime)->format('g:i A').' - '.
                Carbon::createFromFormat('H:i:s', $this->oldEndTime)->format('g:i A'))
            ->line('Now: '.$booking->court->name.', '.$booking->booking_date->format('F j, Y').', '.
                Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A').' - '.
                Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A'))
            ->action('View Booking', route('bookings.show', $booking));
    }
}
