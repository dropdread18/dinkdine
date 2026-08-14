<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    public function __construct(public readonly Payment $payment) {}

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

        return (new MailMessage)
            ->subject("Payment Received - PB-{$booking->id}")
            ->greeting("Hi {$notifiable->name},")
            ->line('We\'ve received your payment for booking PB-'.$booking->id.'.')
            ->line('Amount: ₱'.number_format($payment->amount, 2))
            ->line('Method: '.$payment->method)
            ->action('View Booking', route('bookings.show', $booking))
            ->line('Thank you!');
    }
}
