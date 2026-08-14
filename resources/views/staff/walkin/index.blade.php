@extends('layouts.app', ['title' => 'New Walk-in Booking'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">New Walk-in Booking</h1>

        <div class="flex items-center gap-3 text-sm">
            @if ($prevDate >= $minDate)
                <a href="{{ route('manage.walkin.index', ['date' => $prevDate]) }}" class="text-gray-600 hover:text-gray-900">&lt;</a>
            @else
                <span class="text-gray-300">&lt;</span>
            @endif

            <span class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}</span>

            @if ($nextDate <= $maxDate)
                <a href="{{ route('manage.walkin.index', ['date' => $nextDate]) }}" class="text-gray-600 hover:text-gray-900">&gt;</a>
            @else
                <span class="text-gray-300">&gt;</span>
            @endif
        </div>
    </div>

    @include('partials.availability-grid', ['slotRouteName' => 'manage.walkin.create'])
@endsection
