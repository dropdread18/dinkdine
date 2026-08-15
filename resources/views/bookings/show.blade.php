@extends('layouts.app', ['title' => 'Booking #'.$booking->id])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Booking Confirmed</h1>

    @php
        $statusColor = match ($booking->status) {
            \App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Completed => 'green',
            \App\Enums\BookingStatus::Pending => 'amber',
            \App\Enums\BookingStatus::Cancelled, \App\Enums\BookingStatus::NoShow, \App\Enums\BookingStatus::Expired => 'red',
            default => 'slate',
        };
        $paymentColor = match ($booking->payment_status) {
            \App\Enums\PaymentStatus::Paid => 'green',
            \App\Enums\PaymentStatus::Pending => 'amber',
            \App\Enums\PaymentStatus::Failed => 'red',
            default => 'slate',
        };
    @endphp

    <x-card class="max-w-sm space-y-2 text-sm">
        <div class="flex justify-between"><span class="text-slate-500">Booking #</span><span class="text-slate-900 font-medium">PB-{{ $booking->id }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Customer</span><span class="text-slate-900 font-medium">{{ $booking->user->name }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Court</span><span class="text-slate-900 font-medium">{{ $booking->court->name }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Date</span><span class="text-slate-900 font-medium">{{ $booking->booking_date->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-slate-500">Time</span>
            <span class="text-slate-900 font-medium">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
            </span>
        </div>
        <div class="flex justify-between"><span class="text-slate-500">Amount</span><span class="text-slate-900 font-medium">₱{{ number_format($booking->price, 2) }}</span></div>
        <div class="flex justify-between items-center"><span class="text-slate-500">Status</span><x-badge :color="$statusColor">{{ $booking->status->label() }}</x-badge></div>
        <div class="flex justify-between items-center"><span class="text-slate-500">Payment</span><x-badge :color="$paymentColor">{{ $booking->payment_status->label() }}</x-badge></div>
        @if ($booking->notes)
            <div class="pt-2 border-t border-slate-100"><span class="text-slate-500">Notes</span><p class="text-slate-900 mt-1">{{ $booking->notes }}</p></div>
        @endif
    </x-card>

    @if (auth()->user()->isCustomer())
        <div class="mt-6 flex gap-4 text-sm items-center">
            <a href="{{ route('bookings.mine') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">View my bookings</a>
            <a href="{{ route('bookings.receipt', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Print Receipt</a>
            @if ($canManage)
                <a href="{{ route('bookings.reschedule', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Reschedule</a>
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Cancel Booking</button>
                </form>
            @elseif ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                <span class="text-slate-400">Too close to start time to cancel or reschedule online — contact the facility directly.</span>
            @endif
        </div>
    @else
        <div class="mt-6 flex gap-4 text-sm items-center">
            <a href="{{ route('manage.bookings.index') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Back to Bookings</a>
            <a href="{{ route('bookings.receipt', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Print Receipt</a>
            @if ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                <a href="{{ route('manage.bookings.reschedule', $booking) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Reschedule</a>
                <form method="POST" action="{{ route('manage.bookings.cancel', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Cancel Booking</button>
                </form>
            @endif
        </div>

        @if (auth()->user()->isAdmin())
            @if ($booking->status === \App\Enums\BookingStatus::Cancelled && $booking->payment?->status === \App\Enums\PaymentStatus::Paid)
                <p class="text-amber-700 text-sm mt-4 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">This booking is cancelled but the payment is still marked Paid — refund it below if applicable.</p>
            @endif
            @include('partials.payment-actions', ['payment' => $booking->payment])
        @endif
    @endif
@endsection
