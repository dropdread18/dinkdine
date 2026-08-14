<div>
    @if ($error)
        <div class="mb-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-xl px-4 py-3">{{ $error }}</div>
    @endif

    @if ($reviewing)
        <div class="max-w-md">
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Review Your Bookings</h2>

            <div class="space-y-2 mb-4">
                @foreach ($selected as $key => $slot)
                    <div class="bg-white border border-slate-200 rounded-xl p-3 text-sm flex items-center justify-between shadow-sm">
                        <div>
                            <div class="font-medium text-slate-900">{{ $slot['court_name'] }}</div>
                            <div class="text-slate-500">
                                {{ \Illuminate\Support\Carbon::parse($slot['date'])->format('M j, Y') }},
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['start_time'])->format('g:i A') }} -
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['end_time'])->format('g:i A') }}
                            </div>
                        </div>
                        <button type="button" wire:click="removeSlot('{{ $key }}')" class="text-red-600 hover:text-red-700 underline underline-offset-2 text-sm">Remove</button>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between text-sm font-medium mb-4 px-1">
                <span class="text-slate-600">Total</span>
                <span class="text-slate-900 text-base">₱{{ number_format($totalPrice, 2) }}</span>
            </div>

            @guest
                <div class="border border-slate-200 rounded-xl p-4 mb-4 space-y-3 bg-teal-50/40">
                    <p class="text-sm text-slate-600">Booking without an account? Just tell us who you are.</p>

                    <div>
                        <label for="guest-name" class="block text-sm font-medium text-slate-700">Name</label>
                        <input id="guest-name" type="text" wire:model="guestName" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="guest-email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input id="guest-email" type="email" wire:model="guestEmail" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="guest-phone" class="block text-sm font-medium text-slate-700">Phone</label>
                        <input id="guest-phone" type="text" wire:model="guestPhone" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <p class="text-xs text-slate-500">
                        Already have an account? <a href="{{ route('login') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Log in</a> first to book under it.
                    </p>
                </div>
            @endguest

            <label for="grid-notes" class="block text-sm font-medium text-slate-700">Notes (optional, applies to all)</label>
            <textarea id="grid-notes" wire:model="notes" rows="2" class="mt-1 mb-4 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500"></textarea>

            <div class="flex gap-2">
                <x-button type="button" variant="secondary" wire:click="backToGrid" class="flex-1">Back</x-button>
                <x-button type="button" wire:click="confirmBookings" wire:loading.attr="disabled" class="flex-1">
                    Confirm {{ count($selected) }} Booking{{ count($selected) === 1 ? '' : 's' }}
                </x-button>
            </div>
        </div>
    @else
        <div class="flex flex-wrap gap-4 text-xs text-slate-600 mb-4">
            <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 align-middle mr-1.5"></span>Available</span>
            <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-teal-600 align-middle mr-1.5"></span>Selected</span>
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
                                        $court = $courtAvailability->court;
                                        $key = "{$court->id}|{$slot->startTime}";
                                        $isSelected = isset($selected[$key]);
                                        $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                        $bookable = $slot->status === $slotStatus::Available && $slotStart->gte($bookableFrom);
                                        $classes = match (true) {
                                            $isSelected => 'bg-teal-600 text-white',
                                            $slot->status === $slotStatus::Available => 'bg-emerald-50 text-emerald-700',
                                            $slot->status === $slotStatus::Booked => 'bg-red-50 text-red-700',
                                            $slot->status === $slotStatus::Pending => 'bg-amber-50 text-amber-700',
                                            default => 'bg-slate-100 text-slate-400',
                                        };
                                    @endphp
                                    <td class="py-1 px-2">
                                        @if ($bookable || $isSelected)
                                            <button type="button"
                                                    wire:click="toggleSlot({{ $court->id }}, '{{ addslashes($court->name) }}', '{{ $slot->startTime }}', '{{ $slot->endTime }}')"
                                                    class="block w-full text-center rounded-lg px-2 py-1.5 font-medium {{ $classes }} hover:opacity-80 transition-opacity">
                                                {{ $isSelected ? 'Selected' : $slot->status->label() }}
                                            </button>
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

        @if (! empty($selected))
            <div class="sticky bottom-4 mt-4 bg-white border border-slate-200 rounded-2xl shadow-lg p-4 flex items-center justify-between max-w-md">
                <span class="text-sm text-slate-700">
                    {{ count($selected) }} slot{{ count($selected) === 1 ? '' : 's' }} selected &middot; <span class="font-semibold text-slate-900">₱{{ number_format($totalPrice, 2) }}</span>
                </span>
                <x-button type="button" wire:click="startReview">Review &amp; Confirm</x-button>
            </div>
        @endif
    @endif
</div>
