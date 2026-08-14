@extends('layouts.app', ['title' => 'Payments'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Payments</h1>

    <form method="GET" action="{{ route('manage.payments.index') }}" class="flex flex-wrap gap-2 mb-4 text-sm">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, email, or booking #"
               class="rounded border-gray-300 shadow-sm w-64">

        <select name="status" class="rounded border-gray-300 shadow-sm">
            <option value="">Any Status</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <button type="submit" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">Filter</button>
        <a href="{{ route('manage.payments.index') }}" class="text-gray-600 underline self-center">Clear</a>
    </form>

    @if ($payments->isEmpty())
        <p class="text-gray-500">No payments match these filters.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Booking</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Customer</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Amount</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Method</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Paid At</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-600">PB-{{ $payment->booking->id }}</td>
                            <td class="py-2 pr-4 text-gray-900">{{ $payment->booking->user->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $payment->method ?: '—' }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $payment->status->label() }}</td>
                            <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">{{ $payment->paid_at?->format('M j, Y g:i A') ?: '—' }}</td>
                            <td class="py-2"><a href="{{ route('bookings.show', $payment->booking) }}" class="text-gray-700 underline">View Booking</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $payments->links() }}</div>
    @endif
@endsection
