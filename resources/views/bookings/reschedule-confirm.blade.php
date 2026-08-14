@extends('layouts.app', ['title' => 'Confirm Reschedule'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">Confirm Reschedule — PB-{{ $booking->id }}</h1>

    <div class="bg-white border rounded-lg p-4 max-w-sm space-y-2 text-sm mb-6">
        <div class="flex justify-between"><span class="text-gray-500">New Court</span><span class="text-gray-900">{{ $court->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">New Date</span><span class="text-gray-900">{{ \Illuminate\Support\Carbon::parse($date)->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-gray-500">New Time</span>
            <span class="text-gray-900">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $startTime)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $endTime)->format('g:i A') }}
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('bookings.reschedule-update', ['booking' => $booking, 'court' => $court]) }}" class="max-w-sm space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="start_time" value="{{ $startTime }}">
        <input type="hidden" name="end_time" value="{{ $endTime }}">

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Confirm Reschedule
        </button>

        <a href="{{ route('bookings.reschedule', $booking) }}" class="block text-center text-sm text-gray-600 underline">
            Choose a different time
        </a>
    </form>
@endsection
