@extends('layouts.app', ['title' => 'Book a Court'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
        $times = $availability['courts'][0]->slots ?? [];
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

    <div class="flex flex-wrap gap-4 text-xs text-gray-600 mb-4">
        <span><span class="inline-block w-3 h-3 rounded-sm bg-green-500 align-middle mr-1"></span>Available</span>
        <span><span class="inline-block w-3 h-3 rounded-sm bg-red-500 align-middle mr-1"></span>Booked</span>
        <span><span class="inline-block w-3 h-3 rounded-sm bg-yellow-400 align-middle mr-1"></span>Pending</span>
        <span><span class="inline-block w-3 h-3 rounded-sm bg-gray-300 align-middle mr-1"></span>Closed</span>
    </div>

    @if ($availability['is_facility_closed'])
        <p class="text-gray-500">The facility is closed on this date. Try another date.</p>
    @elseif (empty($availability['courts']))
        <p class="text-gray-500">No courts are configured yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Time</th>
                        @foreach ($availability['courts'] as $courtAvailability)
                            <th class="text-left font-medium text-gray-500 pb-2 px-2">{{ $courtAvailability->court->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($times as $i => $time)
                        <tr class="border-t">
                            <td class="py-1 pr-4 text-gray-600 whitespace-nowrap">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $time->startTime)->format('g:i A') }}
                            </td>
                            @foreach ($availability['courts'] as $courtAvailability)
                                @php
                                    $slot = $courtAvailability->slots[$i];
                                    $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                    $bookable = $slot->status === \App\Enums\SlotStatus::Available && $slotStart->gte($bookableFrom);
                                    $classes = match ($slot->status) {
                                        \App\Enums\SlotStatus::Available => 'bg-green-100 text-green-800',
                                        \App\Enums\SlotStatus::Booked => 'bg-red-100 text-red-800',
                                        \App\Enums\SlotStatus::Pending => 'bg-yellow-100 text-yellow-800',
                                        \App\Enums\SlotStatus::Closed => 'bg-gray-100 text-gray-400',
                                    };
                                @endphp
                                <td class="py-1 px-2">
                                    @if ($bookable)
                                        <a href="{{ route('bookings.create', ['court' => $courtAvailability->court, 'date' => $date, 'start_time' => $slot->startTime, 'end_time' => $slot->endTime]) }}"
                                           class="block text-center rounded px-2 py-1 {{ $classes }} hover:opacity-75">
                                            {{ $slot->status->label() }}
                                        </a>
                                    @else
                                        <span class="block text-center rounded px-2 py-1 {{ $classes }}">
                                            {{ $slot->status->label() }}
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
