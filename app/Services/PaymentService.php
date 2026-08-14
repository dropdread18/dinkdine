<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentActionException;
use App\Models\Payment;

/**
 * Manual payment confirmation only (Requirements.md §29 - the MVP has no
 * payment gateway). Written so a future gateway integration can call
 * markPaid()/markFailed() from a webhook handler instead of a controller,
 * without this state-transition logic having to move.
 */
class PaymentService
{
    /**
     * @throws PaymentActionException
     */
    public function markPaid(Payment $payment, string $method, ?string $notes = null): Payment
    {
        if ($payment->status === PaymentStatus::Paid) {
            throw new PaymentActionException('This payment is already marked as paid.');
        }

        $payment->update([
            'status' => PaymentStatus::Paid,
            'method' => $method,
            'paid_at' => now(),
            'notes' => $this->appendNote($payment->notes, $notes),
        ]);

        $payment->booking->update(['payment_status' => PaymentStatus::Paid]);

        return $payment->fresh();
    }

    /**
     * @throws PaymentActionException
     */
    public function markFailed(Payment $payment, ?string $reason = null): Payment
    {
        if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
            throw new PaymentActionException('A paid or refunded payment cannot be marked as failed - use refund instead.');
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'notes' => $this->appendNote($payment->notes, $reason ?: 'Payment failed'),
        ]);

        $payment->booking->update(['payment_status' => PaymentStatus::Failed]);

        return $payment->fresh();
    }

    /**
     * @throws PaymentActionException
     */
    public function refund(Payment $payment, bool $partial = false, ?string $reason = null): Payment
    {
        if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
            throw new PaymentActionException('Only a paid payment can be refunded.');
        }

        $status = $partial ? PaymentStatus::PartiallyRefunded : PaymentStatus::Refunded;
        $label = $partial ? 'Partially refunded' : 'Refunded';

        $payment->update([
            'status' => $status,
            'notes' => $this->appendNote($payment->notes, $label.($reason ? ": {$reason}" : '')),
        ]);

        $payment->booking->update(['payment_status' => $status]);

        return $payment->fresh();
    }

    private function appendNote(?string $existing, ?string $new): ?string
    {
        if (! $new) {
            return $existing;
        }

        return trim(($existing ? $existing."\n" : '').$new);
    }
}
