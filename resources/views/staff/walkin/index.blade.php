@extends('layouts.app', ['title' => 'Walk-in Booking'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Walk-in Booking</h1>
            <p class="text-sm text-slate-500 mt-0.5">Check any court's availability for the day, or click an open slot to book a walk-in customer.</p>
        </div>

        <div class="flex items-center gap-1 text-sm bg-white border border-slate-200 rounded-lg shadow-sm p-1">
            @if ($prevDate >= $minDate)
                <a href="{{ route('manage.walkin.index', ['date' => $prevDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&lt;</a>
            @else
                <span class="flex h-8 w-8 items-center justify-center text-slate-300">&lt;</span>
            @endif

            <input type="date" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}" aria-label="Jump to date"
                   onchange="if (this.value) window.location.href = '{{ route('manage.walkin.index', ['date' => '__DATE__']) }}'.replace('__DATE__', this.value)"
                   class="font-medium text-slate-900 px-2 bg-transparent border-0 focus:outline-none cursor-pointer">

            @if ($nextDate <= $maxDate)
                <a href="{{ route('manage.walkin.index', ['date' => $nextDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&gt;</a>
            @else
                <span class="flex h-8 w-8 items-center justify-center text-slate-300">&gt;</span>
            @endif
        </div>
    </div>

    @include('partials.availability-grid', ['slotRouteName' => 'manage.walkin.create'])
@endsection
