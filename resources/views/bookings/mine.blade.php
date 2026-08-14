@extends('layouts.app', ['title' => 'My Bookings'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">My Bookings</h1>

    <h2 class="text-sm font-medium text-gray-500 uppercase mb-2">Upcoming</h2>
    @forelse ($upcoming as $booking)
        <div class="bg-white border rounded-lg p-3 mb-2 text-sm">
            <div class="flex justify-between">
                <span class="font-medium text-gray-900">{{ $booking->court->name }}</span>
                <span class="text-gray-500">{{ $booking->status->label() }}</span>
            </div>
            <div class="text-gray-600">
                {{ $booking->booking_date->format('F j, Y') }},
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
            </div>
            <div class="mt-2 flex gap-3">
                <a href="{{ route('bookings.show', $booking) }}" class="underline text-gray-600">View</a>
                @if ($eligibleIds->contains($booking->id))
                    <a href="{{ route('bookings.reschedule', $booking) }}" class="underline text-gray-600">Reschedule</a>
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="underline text-red-700">Cancel</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-sm mb-4">No upcoming bookings. <a href="{{ route('bookings.index') }}" class="underline">Book a court</a>.</p>
    @endforelse

    <h2 class="text-sm font-medium text-gray-500 uppercase mt-6 mb-2">Past</h2>
    @forelse ($past as $booking)
        <a href="{{ route('bookings.show', $booking) }}" class="block bg-white border rounded-lg p-3 mb-2 text-sm hover:bg-gray-50">
            <div class="flex justify-between">
                <span class="font-medium text-gray-900">{{ $booking->court->name }}</span>
                <span class="text-gray-500">{{ $booking->status->label() }}</span>
            </div>
            <div class="text-gray-600">{{ $booking->booking_date->format('F j, Y') }}</div>
        </a>
    @empty
        <p class="text-gray-500 text-sm">No past bookings.</p>
    @endforelse
@endsection
