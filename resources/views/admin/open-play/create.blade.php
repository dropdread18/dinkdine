@extends('layouts.app', ['title' => 'Schedule Open Play'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-[360px_1fr] gap-6 items-start">
        <x-card class="order-1 md:sticky md:top-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Schedule Open Play</h2>

            <form method="POST" action="{{ route('admin.open-play.store') }}" class="space-y-4">
                @csrf
                @include('admin.open-play._form')

                <x-button type="submit" class="w-full">Schedule Open Play</x-button>

                <a href="{{ route('admin.open-play.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
            </form>
        </x-card>

        <div class="order-2">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Schedule</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Click any open time below to fill in the form on the left - no need to type the date or time by hand. Click more than one court to run the same event on all of them.</p>
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

            @include('partials.availability-grid', ['readOnly' => true, 'fillFormOnClick' => true])
        </div>
    </div>

    <script>
        function fillSessionSlot(courtId, date, startTime, endTime) {
            const checkbox = document.querySelector('input[name="court_ids[]"][value="' + courtId + '"]');
            if (checkbox) {
                checkbox.checked = true;
                checkbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            document.getElementById('session_date').value = date;
            document.getElementById('start_time').value = startTime;
            document.getElementById('end_time').value = endTime;
        }
    </script>
@endsection
