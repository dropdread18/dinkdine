@extends('layouts.app', ['title' => 'Payments'])

@section('content')
    <x-page-header title="Payments" />

    <form method="GET" action="{{ route('manage.payments.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, email, or booking #"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-teal-500 focus:ring-teal-500">

        <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <option value="">Any Status</option>
            @foreach (\App\Enums\PaymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <x-button type="submit">Filter</x-button>
        <x-button tag="a" href="{{ route('manage.payments.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($payments->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No payments match these filters.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Booking</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Customer</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Amount</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Method</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Paid At</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-500">PB-{{ $payment->booking->id }}</td>
                            <td class="py-3 pr-4 text-slate-900 font-medium">{{ $payment->booking->user->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $payment->method ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $payment->status->label() }}</td>
                            <td class="py-3 pr-4 text-slate-600 whitespace-nowrap">{{ $payment->paid_at?->format('M j, Y g:i A') ?: '—' }}</td>
                            <td class="py-3 pr-4"><a href="{{ route('bookings.show', $payment->booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">View Booking</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $payments->links() }}</div>
    @endif
@endsection
