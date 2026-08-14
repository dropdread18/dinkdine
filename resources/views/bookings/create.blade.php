@extends('layouts.app', ['title' => 'Confirm Booking'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">Confirm Booking</h1>

    <div class="bg-white border rounded-lg p-4 max-w-sm space-y-2 text-sm mb-6">
        <div class="flex justify-between"><span class="text-gray-500">Court</span><span class="text-gray-900">{{ $court->name }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Date</span><span class="text-gray-900">{{ \Illuminate\Support\Carbon::parse($date)->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-gray-500">Time</span>
            <span class="text-gray-900">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $startTime)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $endTime)->format('g:i A') }}
            </span>
        </div>
        <div class="flex justify-between"><span class="text-gray-500">Price</span><span class="text-gray-900">₱{{ number_format($court->hourly_rate, 2) }}</span></div>
    </div>

    <form method="POST" action="{{ route('bookings.store', $court) }}" class="max-w-sm space-y-4">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="start_time" value="{{ $startTime }}">
        <input type="hidden" name="end_time" value="{{ $endTime }}">

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded border-gray-300 shadow-sm">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Confirm Booking
        </button>

        <a href="{{ route('bookings.index', ['date' => $date]) }}" class="block text-center text-sm text-gray-600 underline">
            Choose a different time
        </a>
    </form>
@endsection
