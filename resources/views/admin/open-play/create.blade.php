@extends('layouts.app', ['title' => 'Schedule Open Play'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">
        <div>
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Schedule</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Check availability here, then fill in the form to schedule Open Play - no need to switch tabs.</p>
                </div>

                <div class="flex items-center gap-1 text-sm bg-white border border-slate-200 rounded-lg shadow-sm p-1">
                    @if ($prevDate >= $minDate)
                        <a href="{{ route('admin.open-play.create', ['date' => $prevDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&lt;</a>
                    @else
                        <span class="flex h-8 w-8 items-center justify-center text-slate-300">&lt;</span>
                    @endif

                    <input type="date" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}" aria-label="Jump to date"
                           onchange="if (this.value) window.location.href = '{{ route('admin.open-play.create', ['date' => '__DATE__']) }}'.replace('__DATE__', this.value)"
                           class="font-medium text-slate-900 px-2 bg-transparent border-0 focus:outline-none cursor-pointer">

                    @if ($nextDate <= $maxDate)
                        <a href="{{ route('admin.open-play.create', ['date' => $nextDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&gt;</a>
                    @else
                        <span class="flex h-8 w-8 items-center justify-center text-slate-300">&gt;</span>
                    @endif
                </div>
            </div>

            @include('partials.availability-grid', ['readOnly' => true])
        </div>

        <x-card class="lg:sticky lg:top-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Schedule Open Play</h2>

            <form method="POST" action="{{ route('admin.open-play.store') }}" class="space-y-4">
                @csrf
                @include('admin.open-play._form')

                <x-button type="submit" class="w-full">Schedule Open Play</x-button>

                <a href="{{ route('admin.open-play.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
            </form>
        </x-card>
    </div>
@endsection
