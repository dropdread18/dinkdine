@extends('layouts.app', ['title' => 'Book a Court'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Book a Court</h1>

        <div class="flex items-center gap-3 text-sm">
            @if ($prevDate >= $minDate)
                <a href="{{ route('bookings.index', ['date' => $prevDate]) }}" class="text-gray-600 hover:text-gray-900">&lt;</a>
            @else
                <span class="text-gray-300">&lt;</span>
            @endif

            <span class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}</span>

            @if ($nextDate <= $maxDate)
                <a href="{{ route('bookings.index', ['date' => $nextDate]) }}" class="text-gray-600 hover:text-gray-900">&gt;</a>
            @else
                <span class="text-gray-300">&gt;</span>
            @endif
        </div>
    </div>

    <livewire:booking-grid :date="$date" :key="$date" />
@endsection
