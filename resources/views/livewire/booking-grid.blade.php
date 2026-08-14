<div>
    @if ($error)
        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded p-2">{{ $error }}</div>
    @endif

    @if ($reviewing)
        <div class="max-w-md">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Review Your Bookings</h2>

            <div class="space-y-2 mb-4">
                @foreach ($selected as $key => $slot)
                    <div class="bg-white border rounded-lg p-3 text-sm flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900">{{ $slot['court_name'] }}</div>
                            <div class="text-gray-600">
                                {{ \Illuminate\Support\Carbon::parse($slot['date'])->format('M j, Y') }},
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['start_time'])->format('g:i A') }} -
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['end_time'])->format('g:i A') }}
                            </div>
                        </div>
                        <button type="button" wire:click="removeSlot('{{ $key }}')" class="text-red-700 underline text-sm">Remove</button>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between text-sm font-medium mb-4">
                <span class="text-gray-700">Total</span>
                <span class="text-gray-900">₱{{ number_format($totalPrice, 2) }}</span>
            </div>

            @guest
                <div class="border rounded-lg p-3 mb-4 space-y-3">
                    <p class="text-sm text-gray-500">Booking without an account? Just tell us who you are.</p>

                    <div>
                        <label for="guest-name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input id="guest-name" type="text" wire:model="guestName" class="mt-1 block w-full rounded border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label for="guest-email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="guest-email" type="email" wire:model="guestEmail" class="mt-1 block w-full rounded border-gray-300 shadow-sm text-sm">
                    </div>
                    <div>
                        <label for="guest-phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input id="guest-phone" type="text" wire:model="guestPhone" class="mt-1 block w-full rounded border-gray-300 shadow-sm text-sm">
                    </div>
                    <p class="text-xs text-gray-400">
                        Already have an account? <a href="{{ route('login') }}" class="underline">Log in</a> first to book under it.
                    </p>
                </div>
            @endguest

            <label for="grid-notes" class="block text-sm font-medium text-gray-700">Notes (optional, applies to all)</label>
            <textarea id="grid-notes" wire:model="notes" rows="2" class="mt-1 mb-4 block w-full rounded border-gray-300 shadow-sm"></textarea>

            <div class="flex gap-2">
                <button type="button" wire:click="backToGrid" class="flex-1 border border-gray-300 rounded py-2 text-sm">Back</button>
                <button type="button" wire:click="confirmBookings" wire:loading.attr="disabled" class="flex-1 bg-gray-900 text-white rounded py-2 text-sm font-medium">
                    Confirm {{ count($selected) }} Booking{{ count($selected) === 1 ? '' : 's' }}
                </button>
            </div>
        </div>
    @else
        <div class="flex flex-wrap gap-4 text-xs text-gray-600 mb-4">
            <span><span class="inline-block w-3 h-3 rounded-sm bg-green-500 align-middle mr-1"></span>Available</span>
            <span><span class="inline-block w-3 h-3 rounded-sm bg-blue-500 align-middle mr-1"></span>Selected</span>
            <span><span class="inline-block w-3 h-3 rounded-sm bg-red-500 align-middle mr-1"></span>Booked</span>
            <span><span class="inline-block w-3 h-3 rounded-sm bg-yellow-400 align-middle mr-1"></span>Pending</span>
            <span><span class="inline-block w-3 h-3 rounded-sm bg-gray-300 align-middle mr-1"></span>Closed</span>
        </div>

        @if ($availability['is_facility_closed'])
            <p class="text-gray-500">The facility is closed on this date. Try another date.</p>
        @elseif (empty($availability['courts']))
            <p class="text-gray-500">No courts are configured yet.</p>
        @else
            @php $times = $availability['courts'][0]->slots ?? []; @endphp
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
                                        $court = $courtAvailability->court;
                                        $key = "{$court->id}|{$slot->startTime}";
                                        $isSelected = isset($selected[$key]);
                                        $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                        $bookable = $slot->status === $slotStatus::Available && $slotStart->gte($bookableFrom);
                                        $classes = match (true) {
                                            $isSelected => 'bg-blue-100 text-blue-800',
                                            $slot->status === $slotStatus::Available => 'bg-green-100 text-green-800',
                                            $slot->status === $slotStatus::Booked => 'bg-red-100 text-red-800',
                                            $slot->status === $slotStatus::Pending => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-400',
                                        };
                                    @endphp
                                    <td class="py-1 px-2">
                                        @if ($bookable || $isSelected)
                                            <button type="button"
                                                    wire:click="toggleSlot({{ $court->id }}, '{{ addslashes($court->name) }}', '{{ $slot->startTime }}', '{{ $slot->endTime }}')"
                                                    class="block w-full text-center rounded px-2 py-1 {{ $classes }} hover:opacity-75">
                                                {{ $isSelected ? 'Selected' : $slot->status->label() }}
                                            </button>
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

        @if (! empty($selected))
            <div class="sticky bottom-4 mt-4 bg-white border rounded-lg shadow p-3 flex items-center justify-between max-w-md">
                <span class="text-sm text-gray-700">
                    {{ count($selected) }} slot{{ count($selected) === 1 ? '' : 's' }} selected &middot; ₱{{ number_format($totalPrice, 2) }}
                </span>
                <button type="button" wire:click="startReview" class="bg-gray-900 text-white rounded px-3 py-1.5 text-sm font-medium">
                    Review &amp; Confirm
                </button>
            </div>
        @endif
    @endif
</div>
