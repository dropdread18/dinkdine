@extends('layouts.app', ['title' => 'Walk-in Booking'])

@section('content')
    @php
        $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
        $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();

        // Same alternating-color scheme as partials/availability-grid.blade.php
        // (kept in sync manually - this grid isn't reusing that partial since
        // its bookable cells need to become checkboxes instead of plain links).
        $openPlayVariants = ['bg-cyan-50 text-cyan-700', 'bg-fuchsia-50 text-fuchsia-700'];
        $openPlayBatchOrder = [];
        foreach ($availability['courts'] ?? [] as $courtAvailability) {
            foreach ($courtAvailability->slots as $slot) {
                if ($slot->status === \App\Enums\SlotStatus::OpenPlay && $slot->openPlayGroupKey !== null && ! array_key_exists($slot->openPlayGroupKey, $openPlayBatchOrder)) {
                    $openPlayBatchOrder[$slot->openPlayGroupKey] = count($openPlayBatchOrder) % 2;
                }
            }
        }
        $openPlayClasses = fn (?string $groupKey) => $openPlayVariants[$openPlayBatchOrder[$groupKey] ?? 0];
    @endphp

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Walk-in Booking</h1>
            <p class="text-sm text-slate-500 mt-0.5">Check any court's availability for the day, or check off one or more open slots to book a walk-in customer.</p>
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

    <form method="GET" action="{{ route('manage.walkin.review') }}"
          x-data="{
              count: 0,
              refreshCount() { this.count = document.querySelectorAll('.slot-checkbox:checked').length; },
          }"
          @change="refreshCount()"
          class="{{ ! empty($availability['courts']) ? 'pb-24' : '' }}">
        <div x-data="{ openPlayModal: null }">
            <div class="flex flex-wrap gap-4 text-xs text-slate-600 mb-4">
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 align-middle mr-1.5"></span>Available</span>
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 align-middle mr-1.5"></span>Booked</span>
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-violet-400 align-middle mr-1.5"></span>In Progress</span>
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-300 align-middle mr-1.5"></span>Closed</span>
                <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-400 align-middle mr-1.5"></span>Maintenance</span>
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
                                            $court = $courtAvailability->court;
                                            $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                            $bookable = $slot->status === \App\Enums\SlotStatus::Available && ($slotStart->lte(\Illuminate\Support\Carbon::now()) || $slotStart->gte($bookableFrom));
                                            $isOpenPlay = $slot->status === \App\Enums\SlotStatus::OpenPlay;
                                            $classes = $isOpenPlay ? $openPlayClasses($slot->openPlayGroupKey) : match ($slot->status) {
                                                \App\Enums\SlotStatus::Available => 'bg-green-50 text-green-700',
                                                \App\Enums\SlotStatus::Booked => 'bg-red-50 text-red-700',
                                                \App\Enums\SlotStatus::InProgress => 'bg-violet-50 text-violet-700',
                                                \App\Enums\SlotStatus::Closed => 'bg-slate-100 text-slate-400',
                                                \App\Enums\SlotStatus::Maintenance => 'bg-orange-50 text-orange-600',
                                                \App\Enums\SlotStatus::OpenPlay => '',
                                            };
                                            $slotValue = json_encode(['court_id' => $court->id, 'date' => $date, 'start_time' => $slot->startTime, 'end_time' => $slot->endTime]);
                                        @endphp
                                        <td class="py-1 px-2">
                                            @if ($bookable)
                                                <label class="block text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity cursor-pointer relative has-[:checked]:ring-2 has-[:checked]:ring-blue-500 has-[:checked]:ring-offset-1">
                                                    <input type="checkbox" name="slots[]" value="{{ $slotValue }}" class="slot-checkbox absolute inset-0 opacity-0 cursor-pointer">
                                                    {{ $slot->status->label() }}
                                                </label>
                                            @elseif ($isOpenPlay)
                                                <button type="button"
                                                        @click="openPlayModal = { court: '{{ addslashes($court->name) }}', time: '{{ addslashes(\Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->openPlayStartTime)->format('g:i A')) }} – {{ addslashes(\Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->openPlayEndTime)->format('g:i A')) }}', link: {{ $slot->openPlayLink ? "'".addslashes($slot->openPlayLink)."'" : 'null' }} }"
                                                        class="block w-full text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity cursor-pointer">
                                                    {{ $slot->status->label() }}
                                                </button>
                                            @elseif ($slot->holdExpiresAt)
                                                <span class="block text-center rounded-lg px-2 py-1.5 {{ $classes }} tabular-nums"
                                                      x-data="{
                                                          expiresAt: new Date('{{ $slot->holdExpiresAt }}').getTime(),
                                                          remaining: 0,
                                                          tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
                                                          get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
                                                      }"
                                                      x-init="tick(); setInterval(() => tick(), 1000)">
                                                    <span x-text="timeLabel"></span>
                                                    @if ($slot->bookedByName)
                                                        <span class="block text-[11px] font-normal truncate">{{ $slot->bookedByName }}</span>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="block text-center rounded-lg px-2 py-1.5 {{ $classes }}">
                                                    {{ $slot->status->label() }}
                                                    @if ($slot->bookedByName)
                                                        <span class="block text-[11px] font-normal truncate">{{ $slot->bookedByName }}</span>
                                                    @endif
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

            {{-- Open Play detail modal - see livewire/booking-grid.blade.php's
                 matching copy for why this is a modal rather than a direct link. --}}
            <div x-show="openPlayModal" x-cloak @keydown.escape.window="openPlayModal = null"
                 class="fixed inset-0 z-50 flex items-center justify-center p-6" style="background: rgba(15, 23, 42, 0.6);">
                <div @click="openPlayModal = null" class="absolute inset-0"></div>
                <div class="relative rounded-2xl p-6 max-w-sm w-full flex flex-col gap-2 bg-white">
                    <div class="text-xs font-bold uppercase tracking-wide text-cyan-700" style="letter-spacing: 0.06em;">Open Play</div>
                    <div class="text-lg font-bold text-slate-900" x-text="openPlayModal?.court"></div>
                    <div class="text-sm text-slate-500 mb-2" x-text="openPlayModal?.time"></div>
                    <template x-if="openPlayModal?.link">
                        <a :href="openPlayModal.link" target="_blank" rel="noopener"
                           class="text-center font-bold text-[15px] py-3 rounded-lg bg-accent text-white">
                            Register for this session
                        </a>
                    </template>
                    <template x-if="!openPlayModal?.link">
                        <p class="text-sm text-slate-500">Registration details aren't posted yet — check back soon or contact the facility.</p>
                    </template>
                    <button type="button" @click="openPlayModal = null" class="text-sm font-semibold mt-2 text-slate-600">Close</button>
                </div>
            </div>
        </div>

        <div x-show="count > 0" x-cloak
             class="fixed bottom-0 left-0 right-0 lg:left-[240px] px-5 py-4 flex flex-wrap items-center gap-3"
             style="background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -8px 24px rgba(15,23,42,0.08);">
            <span class="text-sm font-semibold text-slate-700" x-text="count + ' slot' + (count === 1 ? '' : 's') + ' selected'"></span>
            <x-button type="submit">Continue</x-button>
        </div>
    </form>
@endsection
