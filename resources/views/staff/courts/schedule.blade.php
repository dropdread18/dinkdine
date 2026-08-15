@extends('layouts.app', ['title' => 'Court Schedule'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
    @endphp

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Court Schedule</h1>

        <div class="flex items-center gap-1 text-sm bg-white border border-slate-200 rounded-lg shadow-sm p-1">
            <a href="{{ route('manage.courts.schedule', ['court' => $selectedCourtId, 'date' => $prevDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&lt;</a>
            <input type="date" value="{{ $date }}" aria-label="Jump to date"
                   onchange="if (this.value) window.location.href = '{{ route('manage.courts.schedule', ['court' => $selectedCourtId, 'date' => '__DATE__']) }}'.replace('__DATE__', this.value)"
                   class="font-medium text-slate-900 px-2 bg-transparent border-0 focus:outline-none cursor-pointer">
            <a href="{{ route('manage.courts.schedule', ['court' => $selectedCourtId, 'date' => $nextDate]) }}" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-900">&gt;</a>
        </div>
    </div>

    <form method="GET" action="{{ route('manage.courts.schedule') }}" class="mb-6">
        <input type="hidden" name="date" value="{{ $date }}">
        <label for="court" class="block text-sm font-medium text-slate-700 mb-1">Court</label>
        <select id="court" name="court" class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500" onchange="this.form.requestSubmit()">
            @foreach ($courts as $court)
                <option value="{{ $court->id }}" @selected($court->id === $selectedCourtId)>{{ $court->name }}</option>
            @endforeach
        </select>
    </form>

    @if ($isFacilityClosed)
        <x-card class="text-center text-slate-500 text-sm py-8">The facility is closed on this date.</x-card>
    @elseif (! $courtAvailability)
        <x-card class="text-center text-slate-500 text-sm py-8">Select a court to view its schedule.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courtAvailability->slots as $slot)
                        @php
                            $color = match ($slot->status) {
                                \App\Enums\SlotStatus::Available => 'green',
                                \App\Enums\SlotStatus::Booked => 'red',
                                \App\Enums\SlotStatus::Pending => 'amber',
                                default => 'slate',
                            };
                        @endphp
                        <tr class="border-t border-slate-100">
                            <td class="py-2.5 pl-4 pr-4 text-slate-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->startTime)->format('g:i A') }} –
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->endTime)->format('g:i A') }}
                            </td>
                            <td class="py-2.5 pr-4"><x-badge :color="$color">{{ $slot->status->label() }}</x-badge></td>
                            <td class="py-2.5 pr-4">
                                @if ($slot->bookingId)
                                    <a href="{{ route('bookings.show', $slot->bookingId) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">View Booking</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
