@extends('layouts.app', ['title' => 'My Bookings'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-6">My Bookings</h1>

    @php
        $statusColorFor = fn ($status) => match ($status) {
            \App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Completed => 'green',
            \App\Enums\BookingStatus::Pending => 'amber',
            \App\Enums\BookingStatus::Cancelled, \App\Enums\BookingStatus::NoShow, \App\Enums\BookingStatus::Expired => 'red',
            default => 'slate',
        };
    @endphp

    <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Upcoming</h2>
    @forelse ($upcoming as $booking)
        <x-card class="mb-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="font-medium text-slate-900">{{ $booking->court->name }}</span>
                <x-badge :color="$statusColorFor($booking->status)">{{ $booking->status->label() }}</x-badge>
            </div>
            <div class="text-slate-500 mt-1">
                {{ $booking->booking_date->format('F j, Y') }},
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
            </div>
            <div class="mt-3 flex gap-4">
                <a href="{{ route('bookings.show', $booking) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">View</a>
                @if ($eligibleIds->contains($booking->id))
                    <a href="{{ route('bookings.reschedule', $booking) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Reschedule</a>
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Cancel</button>
                    </form>
                @endif
            </div>
        </x-card>

        @if ($booking->status === \App\Enums\BookingStatus::Pending && $booking->hold_expires_at?->isFuture())
            @include('partials.continue-payment', ['booking' => $booking])
        @endif
    @empty
        <x-card class="mb-4 text-sm text-slate-500">
            No upcoming bookings. <a href="{{ route('bookings.index') }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Book a court</a>.
        </x-card>
    @endforelse

    <h2 class="text-sm font-medium text-slate-500 uppercase mt-8 mb-3">Past</h2>
    @forelse ($past as $booking)
        <a href="{{ route('bookings.show', $booking) }}" class="block bg-white border border-slate-200 rounded-2xl shadow-sm p-4 mb-3 text-sm hover:border-blue-200 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center">
                <span class="font-medium text-slate-900">{{ $booking->court->name }}</span>
                <x-badge :color="$statusColorFor($booking->status)">{{ $booking->status->label() }}</x-badge>
            </div>
            <div class="text-slate-500 mt-1">{{ $booking->booking_date->format('F j, Y') }}</div>
        </a>
    @empty
        <p class="text-slate-500 text-sm">No past bookings.</p>
    @endforelse
@endsection
