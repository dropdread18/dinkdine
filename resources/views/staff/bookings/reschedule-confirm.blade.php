@extends('layouts.app', ['title' => 'Confirm Reschedule'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Confirm Reschedule — PB-{{ $booking->id }}</h1>

    <x-card class="max-w-sm space-y-2 text-sm mb-6">
        <div class="flex justify-between"><span class="text-slate-500">Customer</span><span class="text-slate-900 font-medium">{{ $booking->user->name }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">New Court</span><span class="text-slate-900 font-medium">{{ $court->name }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">New Date</span><span class="text-slate-900 font-medium">{{ \Illuminate\Support\Carbon::parse($date)->format('F j, Y') }}</span></div>
        <div class="flex justify-between">
            <span class="text-slate-500">New Time</span>
            <span class="text-slate-900 font-medium">
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $startTime)->format('g:i A') }} -
                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $endTime)->format('g:i A') }}
            </span>
        </div>
    </x-card>

    <form method="POST" action="{{ route('manage.bookings.reschedule-update', ['booking' => $booking, 'court' => $court]) }}" class="max-w-sm space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="start_time" value="{{ $startTime }}">
        <input type="hidden" name="end_time" value="{{ $endTime }}">

        <x-button type="submit" class="w-full">Confirm Reschedule</x-button>

        <a href="{{ route('manage.bookings.reschedule', $booking) }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Choose a different time
        </a>
    </form>
@endsection
