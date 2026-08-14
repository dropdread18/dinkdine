{{-- Expects: $date, $availability, $bookableFrom, $slotRouteName (route that takes court/date/start_time/end_time,
     plus any keys in $extraRouteParams, e.g. ['booking' => $booking] for the reschedule flow) --}}
@php $extraRouteParams = $extraRouteParams ?? []; @endphp

<div class="flex flex-wrap gap-4 text-xs text-slate-600 mb-4">
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 align-middle mr-1.5"></span>Available</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 align-middle mr-1.5"></span>Booked</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400 align-middle mr-1.5"></span>Pending</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-300 align-middle mr-1.5"></span>Closed</span>
</div>

@if ($availability['is_facility_closed'])
    <x-card class="text-center text-slate-500 text-sm py-8">The facility is closed on this date. Try another date.</x-card>
@elseif (empty($availability['courts']))
    <x-card class="text-center text-slate-500 text-sm py-8">No courts are configured yet.</x-card>
@else
    @php $times = $availability['courts'][0]->slots ?? []; @endphp
    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-50">
                    <th class="text-left font-medium text-slate-500 py-2.5 pl-4 pr-4">Time</th>
                    @foreach ($availability['courts'] as $courtAvailability)
                        <th class="text-left font-medium text-slate-500 py-2.5 px-2">{{ $courtAvailability->court->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($times as $i => $time)
                    <tr class="border-t border-slate-100">
                        <td class="py-1.5 pl-4 pr-4 text-slate-500 whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $time->startTime)->format('g:i A') }}
                        </td>
                        @foreach ($availability['courts'] as $courtAvailability)
                            @php
                                $slot = $courtAvailability->slots[$i];
                                $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                $bookable = $slot->status === \App\Enums\SlotStatus::Available && $slotStart->gte($bookableFrom);
                                $classes = match ($slot->status) {
                                    \App\Enums\SlotStatus::Available => 'bg-emerald-50 text-emerald-700',
                                    \App\Enums\SlotStatus::Booked => 'bg-red-50 text-red-700',
                                    \App\Enums\SlotStatus::Pending => 'bg-amber-50 text-amber-700',
                                    \App\Enums\SlotStatus::Closed => 'bg-slate-100 text-slate-400',
                                };
                            @endphp
                            <td class="py-1 px-2">
                                @if ($bookable)
                                    <a href="{{ route($slotRouteName, $extraRouteParams + ['court' => $courtAvailability->court, 'date' => $date, 'start_time' => $slot->startTime, 'end_time' => $slot->endTime]) }}"
                                       class="block text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity">
                                        {{ $slot->status->label() }}
                                    </a>
                                @else
                                    <span class="block text-center rounded-lg px-2 py-1.5 {{ $classes }}">
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
