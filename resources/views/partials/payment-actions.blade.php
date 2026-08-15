{{-- Expects: $payment (nullable Payment) --}}
@if ($payment)
    <x-card class="max-w-sm space-y-3 text-sm mt-4">
        <h2 class="font-medium text-slate-900">Payment</h2>
        <div class="flex justify-between"><span class="text-slate-500">Amount</span><span class="text-slate-900 font-medium">₱{{ number_format($payment->amount, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="text-slate-900 font-medium">{{ $payment->status->label() }}</span></div>
        @if ($payment->method)
            <div class="flex justify-between"><span class="text-slate-500">Method</span><span class="text-slate-900 font-medium">{{ $payment->method }}</span></div>
        @endif
        @if ($payment->reference_number)
            <div class="flex justify-between"><span class="text-slate-500">Reference #</span><span class="text-slate-900 font-medium font-mono">{{ $payment->reference_number }}</span></div>
        @endif
        @if ($payment->paid_at)
            <div class="flex justify-between"><span class="text-slate-500">Paid At</span><span class="text-slate-900 font-medium">{{ $payment->paid_at->format('M j, Y g:i A') }}</span></div>
        @endif

        @php
            $canMarkPaid = in_array($payment->status, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Failed], true);
            $canMarkFailed = in_array($payment->status, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::Pending], true);
            $canRefund = in_array($payment->status, [\App\Enums\PaymentStatus::Paid, \App\Enums\PaymentStatus::PartiallyRefunded], true);
        @endphp

        @if ($canMarkPaid)
            <form method="POST" action="{{ route('manage.payments.mark-paid', $payment) }}" class="border-t border-slate-100 pt-3 space-y-2">
                @csrf
                @method('PATCH')
                <label class="block text-xs font-medium text-slate-700">Mark as Paid</label>
                <select name="method" required class="block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="other">Other</option>
                </select>
                <input type="text" name="notes" placeholder="Reference / notes (optional)" class="block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
                <x-button type="submit" class="w-full">Mark Paid</x-button>
            </form>
        @endif

        @if ($canMarkFailed)
            <form method="POST" action="{{ route('manage.payments.mark-failed', $payment) }}">
                @csrf
                @method('PATCH')
                <x-button type="submit" variant="danger" class="w-full">Mark Failed</x-button>
            </form>
        @endif

        @if ($canRefund)
            <form method="POST" action="{{ route('manage.payments.refund', $payment) }}" class="space-y-2">
                @csrf
                @method('PATCH')
                <input type="text" name="reason" placeholder="Refund reason (optional)" class="block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
                <div class="flex gap-2">
                    <x-button type="submit" name="partial" value="1" variant="secondary" class="flex-1">Partial Refund</x-button>
                    <x-button type="submit" variant="secondary" class="flex-1">Full Refund</x-button>
                </div>
            </form>
        @endif
    </x-card>
@endif
