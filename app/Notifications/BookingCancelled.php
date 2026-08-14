<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class BookingCancelled extends Notification
{
    public function __construct(public readonly Booking $booking) {}

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
            ->subject("Booking Cancelled - PB-{$booking->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your booking for {$booking->court->name} has been cancelled.")
            ->line('Date: '.$booking->booking_date->format('F j, Y'))
            ->line('Time: '.Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A').' - '.Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A'))
            ->line('If you didn\'t request this, please contact the facility.');
    }
}
