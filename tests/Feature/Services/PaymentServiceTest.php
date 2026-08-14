<?php

namespace Tests\Feature\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentActionException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PaymentService;
    }

    public function test_mark_paid_updates_payment_and_syncs_booking_payment_status(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $paid = $this->service->markPaid($payment, 'gcash', 'Ref #12345');

        $this->assertSame(PaymentStatus::Paid, $paid->status);
        $this->assertSame('gcash', $paid->method);
        $this->assertNotNull($paid->paid_at);
        $this->assertStringContainsString('Ref #12345', $paid->notes);
        $this->assertSame(PaymentStatus::Paid, $booking->fresh()->payment_status);
    }

    public function test_mark_paid_rejects_an_already_paid_payment(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->expectException(PaymentActionException::class);
        $this->service->markPaid($payment, 'cash');
    }

    public function test_mark_failed_updates_payment_and_syncs_booking(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $failed = $this->service->markFailed($payment, 'Card declined');

        $this->assertSame(PaymentStatus::Failed, $failed->status);
        $this->assertStringContainsString('Card declined', $failed->notes);
        $this->assertSame(PaymentStatus::Failed, $booking->fresh()->payment_status);
    }

    public function test_mark_failed_rejects_a_paid_payment(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $this->expectException(PaymentActionException::class);
        $this->service->markFailed($payment);
    }

    public function test_refund_updates_payment_and_syncs_booking(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $refunded = $this->service->refund($payment, partial: false, reason: 'Customer cancelled');

        $this->assertSame(PaymentStatus::Refunded, $refunded->status);
        $this->assertStringContainsString('Customer cancelled', $refunded->notes);
        $this->assertSame(PaymentStatus::Refunded, $booking->fresh()->payment_status);
    }

    public function test_partial_refund_sets_partially_refunded_status(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);

        $refunded = $this->service->refund($payment, partial: true);

        $this->assertSame(PaymentStatus::PartiallyRefunded, $refunded->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $booking->fresh()->payment_status);
    }

    public function test_a_fully_refunded_payment_can_be_refunded_again_to_full(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->paid()->create(['booking_id' => $booking->id]);
        $this->service->refund($payment, partial: true);

        $refunded = $this->service->refund($payment->fresh(), partial: false);

        $this->assertSame(PaymentStatus::Refunded, $refunded->status);
    }

    public function test_refund_rejects_a_payment_that_was_never_paid(): void
    {
        $booking = Booking::factory()->create();
        $payment = Payment::factory()->create(['booking_id' => $booking->id]);

        $this->expectException(PaymentActionException::class);
        $this->service->refund($payment);
    }
}
