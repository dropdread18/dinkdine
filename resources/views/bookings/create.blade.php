@extends('layouts.app', ['title' => 'Confirm Booking'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Confirm Booking</h1>

    <x-card class="max-w-sm space-y-2 text-sm mb-6">
        <div class="flex justify-between"><span class="text-slate-500">Court</span><span class="text-slate-900 font-medium">{{ $court->name }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Date</span><span class="text-slate-900 font-medium">{{ \Illuminate\Support\Carbon::parse($date)->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-slate-500">Time</span>
            <span class="text-slate-900 font-medium">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $startTime)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $endTime)->format('g:i A') }}
            </span>
        </div>
        <div class="flex justify-between pt-2 border-t border-slate-100"><span class="text-slate-500">Price</span><span class="text-slate-900 font-semibold">₱{{ number_format($court->hourly_rate, 2) }}</span></div>
    </x-card>

    <form method="POST" action="{{ route('bookings.store', $court) }}" class="max-w-sm space-y-4">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="start_time" value="{{ $startTime }}">
        <input type="hidden" name="end_time" value="{{ $endTime }}">

        <div>
            <label for="notes" class="block text-sm font-medium text-slate-700">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">{{ old('notes') }}</textarea>
        </div>

        <x-button type="submit" class="w-full">Confirm Booking</x-button>

        <a href="{{ route('bookings.index', ['date' => $date]) }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Choose a different time
        </a>
    </form>
@endsection
