<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class BookingConfirmed extends Notification
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
            ->subject("Booking Confirmed - PB-{$booking->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your booking for {$booking->court->name} is confirmed.")
            ->line('Date: '.$booking->booking_date->format('F j, Y'))
            ->line('Time: '.Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A').' - '.Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A'))
            ->line('Amount: ₱'.number_format($booking->price, 2))
            ->action('View Booking', route('bookings.show', $booking))
            ->line('See you on the court!');
    }
}
