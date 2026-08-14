<?php

namespace Tests\Feature\Notifications;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRefunded;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_sends_a_payment_received_notification(): void
    {
        Notification::fake();

        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        (new PaymentService)->markPaid($payment, 'gcash');

        Notification::assertSentTo($booking->user, PaymentReceived::class, function (PaymentReceived $n) use ($payment) {
            return $n->payment->id === $payment->id;
        });
    }

    public function test_mark_failed_does_not_send_any_notification(): void
    {
        Notification::fake();

        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        (new PaymentService)->markFailed($payment, 'Card declined');

        Notification::assertNothingSent();
    }

    public function test_full_refund_sends_a_refunded_notification(): void
    {
        Notification::fake();

        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        (new PaymentService)->refund($payment);

        Notification::assertSentTo($booking->user, PaymentRefunded::class, function (PaymentRefunded $n) {
            return $n->partial === false;
        });
    }

    public function test_partial_refund_sends_a_refunded_notification_marked_partial(): void
    {
        Notification::fake();

        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        (new PaymentService)->refund($payment, partial: true);

        Notification::assertSentTo($booking->user, PaymentRefunded::class, function (PaymentRefunded $n) {
            return $n->partial === true;
        });
    }
}
