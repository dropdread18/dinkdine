<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Alerts the facility's admin/staff that a customer just completed an
 * online booking - so they know without having to check the dashboard.
 * Sent alongside (not instead of) the customer's own BookingConfirmed
 * receipt. Deliberately not sent for walk-in bookings - see
 * BookingService::notifyStaffOfNewBooking() for why.
 */
class NewBookingAlert extends Notification
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
            ->subject("New Booking - PB-{$booking->id}")
            ->greeting('New online booking received.')
            ->line("Customer: {$booking->user->name} ({$booking->user->email})")
            ->line("Court: {$booking->court->name}")
            ->line('Date: '.$booking->booking_date->format('F j, Y'))
            ->line('Time: '.Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A').' - '.Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A'))
            ->line('Amount: ₱'.number_format($booking->price, 2))
            ->action('View Booking', route('bookings.show', $booking));
    }
}
