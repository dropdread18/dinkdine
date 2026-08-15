@extends('layouts.app', ['title' => 'New Walk-in Booking'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">New Walk-in Booking</h1>

        <div class="flex items-center gap-1 text-sm bg-white border border-slate-200 rounded-lg shadow-sm p-1">
            @if ($prevDate >= $minDate)
                <a href="{{ route('manage.walkin.index', ['date' => $prevDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&lt;</a>
            @else
                <span class="flex h-8 w-8 items-center justify-center text-slate-300">&lt;</span>
            @endif

            <div class="relative">
                <span class="block font-medium text-slate-900 px-2 cursor-pointer">{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}</span>
                <input type="date" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}" aria-label="Jump to date"
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                       onchange="if (this.value) window.location.href = '{{ route('manage.walkin.index', ['date' => '__DATE__']) }}'.replace('__DATE__', this.value)">
            </div>

            @if ($nextDate <= $maxDate)
                <a href="{{ route('manage.walkin.index', ['date' => $nextDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&gt;</a>
            @else
                <span class="flex h-8 w-8 items-center justify-center text-slate-300">&gt;</span>
            @endif
        </div>
    </div>

    @include('partials.availability-grid', ['slotRouteName' => 'manage.walkin.create'])
@endsection
