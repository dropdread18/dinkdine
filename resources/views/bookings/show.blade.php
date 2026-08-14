@extends('layouts.app', ['title' => 'Booking #'.$booking->id])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">Booking Confirmed</h1>

    <div class="bg-white border rounded-lg p-4 max-w-sm space-y-2 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">Booking #</span><span class="text-gray-900">PB-{{ $booking->id }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Customer</span><span class="text-gray-900">{{ $booking->user->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Court</span><span class="text-gray-900">{{ $booking->court->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Date</span><span class="text-gray-900">{{ $booking->booking_date->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-gray-500">Time</span>
            <span class="text-gray-900">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
            </span>
        </div>
        <div class="flex justify-between"><span class="text-gray-500">Amount</span><span class="text-gray-900">₱{{ number_format($booking->price, 2) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="text-gray-900">{{ $booking->status->label() }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Payment</span><span class="text-gray-900">{{ $booking->payment_status->label() }}</span></div>
        @if ($booking->notes)
            <div class="pt-2 border-t"><span class="text-gray-500">Notes</span><p class="text-gray-900">{{ $booking->notes }}</p></div>
        @endif
    </div>

    @if (auth()->user()->isCustomer())
        <div class="mt-6 flex gap-3 text-sm">
            <a href="{{ route('bookings.mine') }}" class="underline text-gray-600">View my bookings</a>
            @if ($canManage)
                <a href="{{ route('bookings.reschedule', $booking) }}" class="underline text-gray-600">Reschedule</a>
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="underline text-red-700">Cancel Booking</button>
                </form>
            @elseif ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                <span class="text-gray-400">Too close to start time to cancel or reschedule online — contact the facility directly.</span>
            @endif
        </div>
    @else
        <div class="mt-6 flex gap-3 text-sm">
            <a href="{{ route('manage.bookings.index') }}" class="underline text-gray-600">Back to Bookings</a>
            @if ($booking->status !== \App\Enums\BookingStatus::Cancelled)
                <a href="{{ route('manage.bookings.reschedule', $booking) }}" class="underline text-gray-600">Reschedule</a>
                <form method="POST" action="{{ route('manage.bookings.cancel', $booking) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="underline text-red-700">Cancel Booking</button>
                </form>
            @endif
        </div>
    @endif
@endsection
