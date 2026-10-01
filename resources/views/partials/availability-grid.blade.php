{{-- Expects: $date, $availability, $bookableFrom, $slotRouteName (route that takes court/date/start_time/end_time,
     plus any keys in $extraRouteParams, e.g. ['booking' => $booking] for the reschedule flow).
     Pass $readOnly => true (and omit $slotRouteName/$bookableFrom) for a pure view - no slot is ever
     clickable for booking, regardless of status - used by the Organizer schedule view. Open Play and
     Training Session slots are always clickable (even in read-only mode) to show that session's own
     detail modal.
     Pass $showCustomerNames => true only from staff/admin-facing pages - it shows who booked a slot
     instead of a bare "Booked"/"In Progress" label, and who a Training Session is for. Never pass this
     from a customer-facing view (e.g. bookings/reschedule.blade.php), since that would show one
     customer another customer's name.
     Pass $fillFormOnClick => true only from the Training Session / Open Play create pages - an
     Available cell becomes a toggle button (see the toggleSessionSlot script below) that fills the
     sibling form's date/start/end fields, and can be clicked again on an adjacent hour in the same
     court column to extend the selection into a longer block (clicking a non-adjacent cell, or a
     cell in a different court's column, starts a new selection instead). Which form field(s) the
     court itself lands in differs per page (a single court_id select for Training Session, a
     court_ids[] checkbox list for Open Play), so the including page must define a
     `window.applySessionCourtSelection(courtId)` function to handle that part. --}}
@php
    $extraRouteParams = $extraRouteParams ?? [];
    $readOnly = $readOnly ?? false;
    $showCustomerNames = $showCustomerNames ?? false;
    $fillFormOnClick = $fillFormOnClick ?? false;
    // Two color variants for Open Play, alternated by batch - see
    // livewire/booking-grid.blade.php for why (distinguishing two
    // different Open Play EVENTS that land back-to-back on one day, while
    // keeping one event that spans multiple courts a single color).
    // Assigned in order of first appearance, not by hashing the key - a
    // hash has a real 50/50 chance of two different events landing on the
    // SAME color by coincidence, which defeats the point.
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

<div x-data="{ openPlayModal: null, trainingModal: null }">
    <div class="flex flex-wrap gap-4 text-xs text-slate-600 mb-4">
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500 align-middle mr-1.5"></span>Available</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 align-middle mr-1.5"></span>Booked</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-violet-400 align-middle mr-1.5"></span>In Progress</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-300 align-middle mr-1.5"></span>Closed</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-400 align-middle mr-1.5"></span>Maintenance</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-cyan-500 align-middle mr-1.5"></span>Open Play</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-indigo-500 align-middle mr-1.5"></span>Training Session</span>
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
                                    $bookable = ! $readOnly && $slot->status === \App\Enums\SlotStatus::Available && ($slotStart->lte(\Illuminate\Support\Carbon::now()) || $slotStart->gte($bookableFrom));
                                    $fillable = $fillFormOnClick && $slot->status === \App\Enums\SlotStatus::Available;
                                    $isOpenPlay = $slot->status === \App\Enums\SlotStatus::OpenPlay;
                                    $isTrainingSession = $slot->status === \App\Enums\SlotStatus::TrainingSession;
                                    $classes = $isOpenPlay ? $openPlayClasses($slot->openPlayGroupKey) : match ($slot->status) {
                                        \App\Enums\SlotStatus::Available => 'bg-green-50 text-green-700',
                                        \App\Enums\SlotStatus::Booked => 'bg-red-50 text-red-700',
                                        \App\Enums\SlotStatus::InProgress => 'bg-violet-50 text-violet-700',
                                        \App\Enums\SlotStatus::Closed => 'bg-slate-100 text-slate-400',
                                        \App\Enums\SlotStatus::Maintenance => 'bg-orange-50 text-orange-600',
                                        \App\Enums\SlotStatus::TrainingSession => 'bg-indigo-50 text-indigo-700',
                                        \App\Enums\SlotStatus::OpenPlay => '',
                                    };
                                @endphp
                                <td class="py-1 px-2">
                                    @if ($bookable)
                                        <a href="{{ route($slotRouteName, $extraRouteParams + ['court' => $courtAvailability->court, 'date' => $date, 'start_time' => $slot->startTime, 'end_time' => $slot->endTime]) }}"
                                           class="block text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity">
                                            {{ $slot->status->label() }}
                                        </a>
                                    @elseif ($fillable)
                                        <button type="button"
                                                data-court-id="{{ $court->id }}"
                                                data-date="{{ $date }}"
                                                data-start="{{ $slot->startTime }}"
                                                data-end="{{ $slot->endTime }}"
                                                onclick="toggleSessionSlot(this)"
                                                class="block w-full text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity cursor-pointer">
                                            {{ $slot->status->label() }}
                                        </button>
                                    @elseif ($isOpenPlay)
                                        <button type="button"
                                                @click="openPlayModal = { court: '{{ addslashes($court->name) }}', time: '{{ addslashes(\Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->openPlayStartTime)->format('g:i A')) }} – {{ addslashes(\Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->openPlayEndTime)->format('g:i A')) }}', link: {{ $slot->openPlayLink ? "'".addslashes($slot->openPlayLink)."'" : 'null' }} }"
                                                class="block w-full text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity cursor-pointer">
                                            {{ $slot->status->label() }}
                                        </button>
                                    @elseif ($isTrainingSession)
                                        <button type="button"
                                                @click="trainingModal = { court: '{{ addslashes($court->name) }}', time: '{{ addslashes(\Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->trainingSessionStartTime)->format('g:i A')) }} – {{ addslashes(\Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->trainingSessionEndTime)->format('g:i A')) }}', customer: {{ $showCustomerNames && $slot->trainingCustomerName ? "'".addslashes($slot->trainingCustomerName)."'" : 'null' }}, link: {{ $showCustomerNames && $slot->trainingSessionLink ? "'".addslashes($slot->trainingSessionLink)."'" : 'null' }} }"
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
                                            @if ($showCustomerNames && $slot->bookedByName)
                                                <span class="block text-[11px] font-normal truncate">{{ $slot->bookedByName }}</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="block text-center rounded-lg px-2 py-1.5 {{ $classes }}">
                                            {{ $slot->status->label() }}
                                            @if ($showCustomerNames && $slot->bookedByName)
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

    {{-- Training Session detail modal - same pattern as Open Play's above,
         but the link is a private reference back to Reclub for
         admin/staff, not a customer signup CTA (nobody signs up for this,
         it's an admin-scheduled slot). Customer/link are only ever
         non-null when $showCustomerNames is true (see the click handler
         above), so both naturally stay blank for Organizer/read-only
         viewers. --}}
    <div x-show="trainingModal" x-cloak @keydown.escape.window="trainingModal = null"
         class="fixed inset-0 z-50 flex items-center justify-center p-6" style="background: rgba(15, 23, 42, 0.6);">
        <div @click="trainingModal = null" class="absolute inset-0"></div>
        <div class="relative rounded-2xl p-6 max-w-sm w-full flex flex-col gap-2 bg-white">
            <div class="text-xs font-bold uppercase tracking-wide text-indigo-700" style="letter-spacing: 0.06em;">Training Session</div>
            <div class="text-lg font-bold text-slate-900" x-text="trainingModal?.court"></div>
            <div class="text-sm text-slate-500" x-text="trainingModal?.time"></div>
            <template x-if="trainingModal?.customer">
                <div class="text-sm text-slate-700 mt-1">Customer: <span class="font-medium" x-text="trainingModal?.customer"></span></div>
            </template>
            <template x-if="trainingModal?.link">
                <a :href="trainingModal.link" target="_blank" rel="noopener" class="text-sm text-blue-600 hover:text-blue-700 underline underline-offset-2 mt-1">View on Reclub</a>
            </template>
            <button type="button" @click="trainingModal = null" class="text-sm font-semibold mt-2 text-slate-600">Close</button>
        </div>
    </div>
</div>

@if ($fillFormOnClick)
    <script>
        // Lets an Available cell be clicked more than once to build a
        // longer contiguous block (e.g. 9-10am then 10-11am => 9-11am),
        // instead of only ever filling a single hour. Scoped to one
        // court column at a time: clicking a cell in a different column,
        // or one that doesn't touch either edge of the current block,
        // starts a fresh single-hour selection there instead - see the
        // doc comment at the top of this partial.
        (function () {
            const highlightClasses = ['ring-2', 'ring-blue-500', 'ring-offset-1'];
            let selectedSlots = [];
            let lastCourtId = null;

            window.toggleSessionSlot = function (btn) {
                const courtId = btn.dataset.courtId;
                const start = btn.dataset.start;
                const end = btn.dataset.end;
                const date = btn.dataset.date;

                const idx = selectedSlots.findIndex((s) => s.el === btn);

                if (idx !== -1) {
                    btn.classList.remove(...highlightClasses);
                    if (selectedSlots.length === 1) {
                        selectedSlots = [];
                        lastCourtId = null;
                        return;
                    }
                    if (idx === 0 || idx === selectedSlots.length - 1) {
                        selectedSlots.splice(idx, 1);
                    } else {
                        // Can't remove a slot from the middle of the block
                        // without leaving a gap - put the highlight back
                        // and ignore the click.
                        btn.classList.add(...highlightClasses);
                        return;
                    }
                } else if (selectedSlots.length > 0 && courtId === lastCourtId
                    && (start === selectedSlots[selectedSlots.length - 1].end || end === selectedSlots[0].start)) {
                    if (start === selectedSlots[selectedSlots.length - 1].end) {
                        selectedSlots.push({ el: btn, start, end });
                    } else {
                        selectedSlots.unshift({ el: btn, start, end });
                    }
                    btn.classList.add(...highlightClasses);
                } else {
                    selectedSlots.forEach((s) => s.el.classList.remove(...highlightClasses));
                    selectedSlots = [{ el: btn, start, end }];
                    btn.classList.add(...highlightClasses);
                }

                lastCourtId = courtId;

                if (selectedSlots.length > 0) {
                    document.getElementById('session_date').value = date;
                    document.getElementById('start_time').value = selectedSlots[0].start;
                    document.getElementById('end_time').value = selectedSlots[selectedSlots.length - 1].end;
                }

                if (typeof window.applySessionCourtSelection === 'function') {
                    window.applySessionCourtSelection(courtId);
                }
            };
        })();
    </script>
@endif
