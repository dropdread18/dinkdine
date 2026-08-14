@extends('layouts.app', ['title' => 'Reschedule Booking'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="flex items-center justify-between mb-2">
        <h1 class="text-xl font-semibold text-gray-900">Reschedule PB-{{ $booking->id }}</h1>

        <div class="flex items-center gap-3 text-sm">
            @if ($prevDate >= $minDate)
                <a href="{{ route('bookings.reschedule', ['booking' => $booking, 'date' => $prevDate]) }}" class="text-gray-600 hover:text-gray-900">&lt;</a>
            @else
                <span class="text-gray-300">&lt;</span>
            @endif

            <span class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}</span>

            @if ($nextDate <= $maxDate)
                <a href="{{ route('bookings.reschedule', ['booking' => $booking, 'date' => $nextDate]) }}" class="text-gray-600 hover:text-gray-900">&gt;</a>
            @else
                <span class="text-gray-300">&gt;</span>
            @endif
        </div>
    </div>

    <p class="text-sm text-gray-500 mb-4">
        Currently: {{ $booking->court->name }}, {{ $booking->booking_date->format('M j, Y') }},
        {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}.
        Pick a new slot below.
    </p>

    @include('partials.availability-grid', ['slotRouteName' => 'bookings.reschedule-form', 'extraRouteParams' => ['booking' => $booking]])
@endsection
