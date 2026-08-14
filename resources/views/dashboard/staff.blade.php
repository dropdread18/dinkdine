@extends('layouts.app', ['title' => 'Staff Dashboard'])

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold text-gray-900">Today's Bookings</h1>
        <a href="{{ route('manage.walkin.index') }}" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">
            New Walk-in Booking
        </a>
    </div>

    @if ($todaysBookings->isEmpty())
        <p class="text-gray-500 text-sm">No bookings today.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Time</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Customer</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($todaysBookings as $booking)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} -
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                            </td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->court->name }}</td>
                            <td class="py-2 pr-4 text-gray-900">{{ $booking->user->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $booking->status->label() }}</td>
                            <td class="py-2"><a href="{{ route('bookings.show', $booking) }}" class="text-gray-700 underline">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <a href="{{ route('manage.bookings.index') }}" class="inline-block mt-6 text-sm underline text-gray-600">View all bookings</a>
@endsection
