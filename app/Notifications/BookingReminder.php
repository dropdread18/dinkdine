<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class BookingReminder extends Notification
{
    /**
     * @param  int  $hoursBefore  24 or 1 (Requirements.md §32) - only affects wording.
     */
    public function __construct(public readonly Booking $booking, public readonly int $hoursBefore) {}

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
        $when = $this->hoursBefore === 1 ? '1 hour' : "{$this->hoursBefore} hours";

        return (new MailMessage)
            ->subject("Reminder: Your booking starts in {$when}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your pickleball booking starts in {$when}.")
            ->line('Court: '.$booking->court->name)
            ->line('Date: '.$booking->booking_date->format('F j, Y'))
            ->line('Time: '.Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A').' - '.Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A'))
            ->action('View Booking', route('bookings.show', $booking));
    }
}
