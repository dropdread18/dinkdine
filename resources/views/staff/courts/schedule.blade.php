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

    <div class="mb-6">
        <label class="block text-sm font-medium text-slate-700 mb-2">Court</label>
        <div class="flex flex-wrap gap-2">
            @foreach ($courts as $court)
                <a href="{{ route('manage.courts.schedule', ['court' => $court->id, 'date' => $date]) }}"
                   class="px-4 py-2 rounded-full text-sm font-semibold border {{ $court->id === $selectedCourtId ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:border-slate-400' }}">
                    {{ $court->name }}
                </a>
            @endforeach
        </div>
    </div>

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
                                \App\Enums\SlotStatus::InProgress => 'blue',
                                default => 'slate',
                            };
                        @endphp
                        <tr class="border-t border-slate-100">
                            <td class="py-2.5 pl-4 pr-4 text-slate-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->startTime)->format('g:i A') }} –
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->endTime)->format('g:i A') }}
                            </td>
                            <td class="py-2.5 pr-4">
                                <div class="flex items-center gap-2">
                                    <x-badge :color="$color">{{ $slot->status->label() }}</x-badge>
                                    @if ($slot->holdExpiresAt)
                                        <span class="text-xs font-semibold text-blue-700 tabular-nums"
                                              x-data="{
                                                  expiresAt: new Date('{{ $slot->holdExpiresAt }}').getTime(),
                                                  remaining: 0,
                                                  tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
                                                  get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
                                              }"
                                              x-init="tick(); setInterval(() => tick(), 1000)"
                                              x-text="timeLabel"></span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2.5 pr-4">
                                @if ($slot->bookingId)
                                    <a href="{{ route('bookings.show', $slot->bookingId) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">View Booking</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
