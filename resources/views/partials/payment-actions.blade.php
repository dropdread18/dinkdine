{{-- Expects: $payment (nullable Payment) --}}
@if ($payment)
    <div class="bg-white border rounded-lg p-4 max-w-sm space-y-3 text-sm mt-4">
        <h2 class="font-medium text-gray-900">Payment</h2>
        <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="text-gray-900">₱{{ number_format($payment->amount, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="text-gray-900">{{ $payment->status->label() }}</span></div>
        @if ($payment->method)
            <div class="flex justify-between"><span class="text-gray-500">Method</span><span class="text-gray-900">{{ $payment->method }}</span></div>
        @endif
        @if ($payment->paid_at)
            <div class="flex justify-between"><span class="text-gray-500">Paid At</span><span class="text-gray-900">{{ $payment->paid_at->format('M j, Y g:i A') }}</span></div>
        @endif

        @php
            $canMarkPaid = in_array($payment->status, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Failed], true);
            $canMarkFailed = in_array($payment->status, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::Pending], true);
            $canRefund = in_array($payment->status, [\App\Enums\PaymentStatus::Paid, \App\Enums\PaymentStatus::PartiallyRefunded], true);
        @endphp

        @if ($canMarkPaid)
            <form method="POST" action="{{ route('manage.payments.mark-paid', $payment) }}" class="border-t pt-3 space-y-2">
                @csrf
                @method('PATCH')
                <label class="block text-xs font-medium text-gray-700">Mark as Paid</label>
                <select name="method" required class="block w-full rounded border-gray-300 shadow-sm text-sm">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="other">Other</option>
                </select>
                <input type="text" name="notes" placeholder="Reference / notes (optional)" class="block w-full rounded border-gray-300 shadow-sm text-sm">
                <button type="submit" class="w-full bg-gray-900 text-white rounded py-1.5 text-sm">Mark Paid</button>
            </form>
        @endif

        @if ($canMarkFailed)
            <form method="POST" action="{{ route('manage.payments.mark-failed', $payment) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="w-full border border-red-300 text-red-700 rounded py-1.5 text-sm">Mark Failed</button>
            </form>
        @endif

        @if ($canRefund)
            <form method="POST" action="{{ route('manage.payments.refund', $payment) }}" class="space-y-2">
                @csrf
                @method('PATCH')
                <input type="text" name="reason" placeholder="Refund reason (optional)" class="block w-full rounded border-gray-300 shadow-sm text-sm">
                <div class="flex gap-2">
                    <button type="submit" name="partial" value="1" class="flex-1 border border-gray-300 rounded py-1.5 text-sm">Partial Refund</button>
                    <button type="submit" class="flex-1 border border-gray-300 rounded py-1.5 text-sm">Full Refund</button>
                </div>
            </form>
        @endif
    </div>
@endif
