{{-- Expects: $date, $availability, $bookableFrom, $slotRouteName (route that takes court/date/start_time/end_time,
     plus any keys in $extraRouteParams, e.g. ['booking' => $booking] for the reschedule flow) --}}
@php $extraRouteParams = $extraRouteParams ?? []; @endphp

<div class="flex flex-wrap gap-4 text-xs text-slate-600 mb-4">
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 align-middle mr-1.5"></span>Available</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 align-middle mr-1.5"></span>Booked</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-violet-400 align-middle mr-1.5"></span>In Progress</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-300 align-middle mr-1.5"></span>Closed</span>
    <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-cyan-500 align-middle mr-1.5"></span>Open Play</span>
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
                            {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $time->startTime)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $time->endTime)->format('g:i A') }}
                        </td>
                        @foreach ($availability['courts'] as $courtAvailability)
                            @php
                                $slot = $courtAvailability->slots[$i];
                                $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                $bookable = $slot->status === \App\Enums\SlotStatus::Available && ($slotStart->lte(\Illuminate\Support\Carbon::now()) || $slotStart->gte($bookableFrom));
                                $classes = match ($slot->status) {
                                    \App\Enums\SlotStatus::Available => 'bg-green-50 text-green-700',
                                    \App\Enums\SlotStatus::Booked => 'bg-red-50 text-red-700',
                                    \App\Enums\SlotStatus::InProgress => 'bg-violet-50 text-violet-700',
                                    \App\Enums\SlotStatus::Closed => 'bg-slate-100 text-slate-400',
                                    \App\Enums\SlotStatus::OpenPlay => 'bg-cyan-50 text-cyan-700',
                                };
                            @endphp
                            <td class="py-1 px-2">
                                @if ($bookable)
                                    <a href="{{ route($slotRouteName, $extraRouteParams + ['court' => $courtAvailability->court, 'date' => $date, 'start_time' => $slot->startTime, 'end_time' => $slot->endTime]) }}"
                                       class="block text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity">
                                        {{ $slot->status->label() }}
                                    </a>
                                @elseif ($slot->holdExpiresAt)
                                    <span class="block text-center rounded-lg px-2 py-1.5 {{ $classes }} tabular-nums"
                                          x-data="{
                                              expiresAt: new Date('{{ $slot->holdExpiresAt }}').getTime(),
                                              remaining: 0,
                                              tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
                                              get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
                                          }"
                                          x-init="tick(); setInterval(() => tick(), 1000)"
                                          x-text="timeLabel"></span>
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
