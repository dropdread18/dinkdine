<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRefunded extends Notification
{
    public function __construct(public readonly Payment $payment, public readonly bool $partial = false) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;
        $booking = $payment->booking;
        $label = $this->partial ? 'partially refunded' : 'refunded';

        return (new MailMessage)
            ->subject("Payment Refunded - PB-{$booking->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your payment for booking PB-{$booking->id} has been {$label}.")
            ->line('Amount: ₱'.number_format($payment->amount, 2))
            ->action('View Booking', route('bookings.show', $booking));
    }
}
