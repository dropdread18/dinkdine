<div style="font-family: var(--db-font, inherit);">
    @if ($error)
        <div class="mb-4 text-sm rounded-xl px-4 py-3" style="background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C;">{{ $error }}</div>
    @endif

    @if ($awaitingPayment)
        {{-- Payment hold: matches Guest Payment Hold.dc.html. One responsive card (the mockup's separate
             mobile/desktop frames differ only in width, not interaction), centered, max 520px wide. --}}
        <div class="max-w-[520px] mx-auto flex flex-col gap-5"
             x-data="{
                 expiresAt: new Date('{{ $holdExpiresAt }}').getTime(),
                 remaining: 0,
                 timerColors: {
                     normal: { text: '#B45309', bg: '#FFFBEB' },
                     warning: { text: '#EA580C', bg: '#FFF7ED' },
                     critical: { text: '#DC2626', bg: '#FEF2F2' },
                 },
                 tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
                 get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
                 get level() { return this.remaining < 60 ? 'critical' : (this.remaining < 300 ? 'warning' : 'normal'); },
             }"
             x-init="tick(); setInterval(() => tick(), 1000)">

            <div class="text-center flex flex-col gap-1.5">
                <div class="text-[13px] font-bold uppercase" style="color: #B45309; letter-spacing: 0.06em;">Payment Required</div>
                <div class="text-[26px] font-extrabold" style="color: var(--db-ink);">Your court is temporarily held</div>
                <div class="text-[15px]" style="color: var(--db-ink-soft);">Complete payment before the hold expires to secure your booking.</div>
            </div>

            <div class="rounded-2xl p-6 flex flex-col gap-4" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                @foreach ($pendingBookings as $booking)
                    @unless ($loop->first)
                        <div class="h-px" style="background: var(--db-border);"></div>
                    @endunless
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-lg font-bold" style="color: var(--db-ink);">{{ $booking->court->name }}</div>
                            <div class="text-sm" style="color: var(--db-ink-soft);">
                                {{ $booking->booking_date->format('F j, Y') }} ·
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                            </div>
                        </div>
                        <div class="text-2xl font-extrabold" style="color: var(--db-ink);">₱{{ number_format($booking->price, 0) }}</div>
                    </div>
                @endforeach

                @if ($pendingBookings->count() > 1)
                    <div class="h-px" style="background: var(--db-border);"></div>
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-semibold" style="color: var(--db-ink-soft);">Total</div>
                        <div class="text-xl font-extrabold" style="color: var(--db-ink);">₱{{ number_format($pendingBookings->sum('price'), 0) }}</div>
                    </div>
                @endif

                <div class="h-px" style="background: var(--db-border);"></div>
                <div class="flex justify-between items-center">
                    <div class="text-sm font-semibold" style="color: var(--db-ink-soft);">Time remaining</div>
                    <div class="text-xl font-extrabold rounded-lg px-3.5 py-1.5 tabular-nums"
                         :style="`color: ${timerColors[level].text}; background: ${timerColors[level].bg};`"
                         x-text="timeLabel"></div>
                </div>
            </div>

            <div class="rounded-2xl p-6 flex flex-col gap-4" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                <div class="text-sm font-bold" style="color: var(--db-ink);">Payment Method</div>
                <div class="flex gap-2.5 flex-wrap">
                    <div class="px-4 py-2.5 rounded-lg text-sm font-bold" style="border: 2px solid var(--db-ink); color: var(--db-ink);">GCash</div>
                    <div class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="border: 1px solid var(--db-border); color: var(--db-ink-faint);">Bank Transfer</div>
                    <div class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="border: 1px solid var(--db-border); color: var(--db-ink-faint);">Cash</div>
                </div>

                @if ($paymentQrCode)
                    <div x-data="{ payMode: 'scan', qrLightbox: false }" class="flex flex-col gap-3">
                        <div class="flex gap-2">
                            <button type="button" @click="payMode = 'scan'"
                                    class="px-4 py-2.5 rounded-lg text-sm"
                                    :style="payMode === 'scan' ? 'border:2px solid var(--db-ink);color:var(--db-ink);font-weight:700;' : 'border:1px solid var(--db-border);color:var(--db-ink-faint);font-weight:600;'">
                                Scan QR Code
                            </button>
                            <button type="button" @click="payMode = 'manual'"
                                    class="px-4 py-2.5 rounded-lg text-sm"
                                    :style="payMode === 'manual' ? 'border:2px solid var(--db-ink);color:var(--db-ink);font-weight:700;' : 'border:1px solid var(--db-border);color:var(--db-ink-faint);font-weight:600;'">
                                Enter Manually
                            </button>
                        </div>

                        <div x-show="payMode === 'scan'" class="flex flex-col items-center gap-2 py-2">
                            <button type="button" @click="qrLightbox = true" class="cursor-zoom-in rounded-lg p-1" style="border: 1px solid var(--db-border); background: #FFFFFF;">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($paymentQrCode) }}" alt="Payment QR code"
                                     class="w-40 h-40 object-contain">
                            </button>
                            <div class="text-xs font-semibold text-center" style="color: var(--db-ink-faint);">Open your payment app and scan this code. Tap it to make it bigger.</div>
                        </div>

                        @if ($paymentInstructions)
                            <div x-show="payMode === 'manual'" class="rounded-xl p-4 text-base font-bold whitespace-pre-line leading-relaxed" style="background: #F8FAF5; border: 1px solid var(--db-border); color: var(--db-ink);">{{ $paymentInstructions }}</div>
                        @endif

                        <div x-show="qrLightbox" x-cloak @keydown.escape.window="qrLightbox = false"
                             class="fixed inset-0 z-50 flex items-center justify-center p-6" style="background: rgba(15, 23, 42, 0.85);">
                            <div @click="qrLightbox = false" class="absolute inset-0"></div>
                            <div class="relative flex flex-col items-center gap-4">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($paymentQrCode) }}" alt="Payment QR code, enlarged"
                                     class="w-[min(80vw,420px)] h-[min(80vw,420px)] object-contain rounded-2xl p-4" style="background: #FFFFFF;">
                                <button type="button" @click="qrLightbox = false"
                                        class="px-5 py-2.5 rounded-lg text-sm font-bold" style="background: var(--db-accent); color: var(--db-accent-ink);">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif ($paymentInstructions)
                    <div class="rounded-xl p-4 text-base font-bold whitespace-pre-line leading-relaxed" style="background: #F8FAF5; border: 1px solid var(--db-border); color: var(--db-ink);">{{ $paymentInstructions }}</div>
                @endif

                <div class="flex flex-col gap-1.5">
                    <label for="payment-reference" class="text-[13px] font-bold" style="color: var(--db-ink);">Payment Reference Number <span style="color: #B91C1C;">*</span></label>
                    <input id="payment-reference" type="text" wire:model.live="paymentReference" required
                           placeholder="e.g. GCASH-REF-88231"
                           class="h-12 rounded-lg px-3.5 text-[15px] font-semibold"
                           style="border: 1px solid var(--db-border); color: var(--db-ink);">
                    @error('paymentReference')
                        <div class="text-xs font-semibold" style="color: #B91C1C;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="payment-proof" class="text-[13px] font-bold" style="color: var(--db-ink);">Upload a Screenshot of Your Receipt (optional)</label>
                    <input id="payment-proof" type="file" wire:model="paymentProof" accept="image/*"
                           class="text-sm rounded-lg px-3.5 py-2.5"
                           style="border: 1px solid var(--db-border); color: var(--db-ink);">
                    <div wire:loading wire:target="paymentProof" class="text-xs font-semibold" style="color: var(--db-ink-faint);">Uploading…</div>
                    @if ($paymentProof)
                        <img src="{{ $paymentProof->temporaryUrl() }}" alt="Receipt preview" class="w-24 h-24 object-cover rounded-lg" style="border: 1px solid var(--db-border);">
                    @endif
                    @error('paymentProof')
                        <div class="text-xs font-semibold" style="color: #B91C1C;">{{ $message }}</div>
                    @enderror
                </div>

                <p class="text-xs -mt-2" style="color: var(--db-ink-faint);">Bring this reference number with you — staff will confirm it against payment when you arrive.</p>
            </div>

            @php $canSubmitPayment = trim((string) $paymentReference) !== ''; @endphp
            <button type="button" wire:click="submitPaymentReference" wire:loading.attr="disabled"
                    class="h-[52px] rounded-lg text-[16px] font-bold text-center"
                    style="background: {{ $canSubmitPayment ? 'var(--db-accent)' : 'var(--db-border)' }}; color: {{ $canSubmitPayment ? 'var(--db-accent-ink)' : 'var(--db-ink-faintest)' }};">
                Confirm Payment
            </button>
        </div>
    @elseif ($reviewing)
        {{-- Review + guest details: restyled to the new system, structurally unchanged from before this pass. --}}
        <div class="max-w-md rounded-2xl p-6" style="background: var(--db-surface); border: 1px solid var(--db-border);">
            <h2 class="text-lg font-bold mb-3" style="color: var(--db-ink);">Review Your Bookings</h2>

            <div class="space-y-2 mb-4">
                @foreach ($selected as $key => $slot)
                    <div class="rounded-xl p-3 text-sm flex items-center justify-between" style="background: #F8FAF5; border: 1px solid var(--db-border);">
                        <div>
                            <div class="font-bold" style="color: var(--db-ink);">{{ $slot['court_name'] }}</div>
                            <div style="color: var(--db-ink-soft);">
                                {{ \Illuminate\Support\Carbon::parse($slot['date'])->format('M j, Y') }},
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['start_time'])->format('g:i A') }} –
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot['end_time'])->format('g:i A') }}
                            </div>
                        </div>
                        <button type="button" wire:click="removeSlot('{{ $key }}')" class="text-sm font-semibold" style="color: #B91C1C;">Remove</button>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between text-sm font-semibold mb-4 px-1">
                <span style="color: var(--db-ink-soft);">Total</span>
                <span class="text-base font-extrabold" style="color: var(--db-ink);">₱{{ number_format($totalPrice, 2) }}</span>
            </div>

            @guest
                <div class="rounded-xl p-4 mb-4 space-y-3" style="background: #F8FAF5; border: 1px solid var(--db-border);">
                    <p class="text-sm" style="color: var(--db-ink-soft);">Booking without an account? Just tell us who you are.</p>

                    <div>
                        <label for="guest-name" class="block text-sm font-semibold mb-1" style="color: var(--db-ink);">Name</label>
                        <input id="guest-name" type="text" wire:model="guestName" class="block w-full rounded-lg text-sm px-3 py-2" style="border: 1px solid var(--db-border);">
                    </div>
                    <div>
                        <label for="guest-email" class="block text-sm font-semibold mb-1" style="color: var(--db-ink);">Email</label>
                        <input id="guest-email" type="email" wire:model="guestEmail" class="block w-full rounded-lg text-sm px-3 py-2" style="border: 1px solid var(--db-border);">
                    </div>
                    <div>
                        <label for="guest-phone" class="block text-sm font-semibold mb-1" style="color: var(--db-ink);">Phone</label>
                        <input id="guest-phone" type="text" wire:model="guestPhone" class="block w-full rounded-lg text-sm px-3 py-2" style="border: 1px solid var(--db-border);">
                    </div>
                    <p class="text-xs" style="color: var(--db-ink-faint);">
                        Already have an account? <a href="{{ route('login') }}" class="font-semibold">Log in</a> first to book under it.
                    </p>
                    <p class="text-xs" style="color: var(--db-ink-faint);">
                        You'll have {{ $paymentHoldMinutes }} minute{{ $paymentHoldMinutes === 1 ? '' : 's' }} to pay and enter a reference number after confirming — your slot is held during that time.
                    </p>
                </div>
            @endguest

            <label for="grid-notes" class="block text-sm font-semibold mb-1" style="color: var(--db-ink);">Notes (optional, applies to all)</label>
            <textarea id="grid-notes" wire:model="notes" rows="2" class="mb-4 block w-full rounded-lg text-sm px-3 py-2" style="border: 1px solid var(--db-border);"></textarea>

            <div class="flex gap-2">
                <button type="button" wire:click="backToGrid" class="flex-1 font-bold text-[15px] py-3 rounded-lg" style="background: var(--db-surface); border: 1px solid var(--db-border); color: var(--db-ink);">Back</button>
                <button type="button" wire:click="confirmBookings" wire:loading.attr="disabled" class="flex-1 font-bold text-[15px] py-3 rounded-lg" style="background: var(--db-accent); color: var(--db-accent-ink);">
                    Confirm {{ count($selected) }} Booking{{ count($selected) === 1 ? '' : 's' }}
                </button>
            </div>
        </div>
    @else
        @php
            $statusMeta = [
                'available' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'dot' => '#22C55E', 'icon' => '●', 'label' => 'Available'],
                'selected' => ['bg' => '#3B82F6', 'border' => '#3B82F6', 'text' => '#FFFFFF', 'dot' => '#3B82F6', 'icon' => '✓', 'label' => 'Selected'],
                'booked' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C', 'dot' => '#EF4444', 'icon' => '●', 'label' => 'Booked'],
                'in_progress' => ['bg' => '#F5F3FF', 'border' => '#DDD6FE', 'text' => '#6D28D9', 'dot' => '#8B5CF6', 'icon' => '◔', 'label' => 'In Progress'],
                'closed' => ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'text' => '#64748B', 'dot' => '#94A3B8', 'icon' => '—', 'label' => 'Closed'],
            ];
            $effectiveMobileCourt = $mobileCourt ?? ($availability['courts'][0]->court->id ?? null);
        @endphp

        {{-- Legend --}}
        <div class="flex flex-wrap gap-3 sm:gap-6 px-4 py-3.5 rounded-xl mb-4" style="background: var(--db-surface); border: 1px solid var(--db-border);">
            @foreach ($statusMeta as $meta)
                <div class="flex items-center gap-1.5 text-[13px] font-semibold" style="color: var(--db-ink-soft);">
                    <span class="inline-block w-2 h-2 rounded-full" style="background: {{ $meta['dot'] }};"></span>
                    {{ $meta['label'] }}
                </div>
            @endforeach
        </div>

        @if ($availability['is_facility_closed'])
            <div class="rounded-2xl p-8 text-center text-sm" style="background: var(--db-surface); border: 1px solid var(--db-border); color: var(--db-ink-faint);">The facility is closed on this date. Try another date.</div>
        @elseif (empty($availability['courts']))
            <div class="rounded-2xl p-8 text-center text-sm" style="background: var(--db-surface); border: 1px solid var(--db-border); color: var(--db-ink-faint);">No courts are configured yet.</div>
        @else
            @php $times = $availability['courts'][0]->slots ?? []; @endphp

            {{-- ============ Desktop: grid + live sidebar (lg and up) ============ --}}
            <div class="hidden lg:grid lg:items-start lg:gap-6" style="grid-template-columns: 1fr 340px;">
                <div class="rounded-2xl p-5 overflow-hidden" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                    <div class="grid gap-2 mb-2" style="grid-template-columns: 96px repeat({{ count($availability['courts']) }}, 1fr);">
                        <div></div>
                        @foreach ($availability['courts'] as $courtAvailability)
                            <div class="text-sm font-bold text-center py-2" style="color: var(--db-ink);">{{ $courtAvailability->court->name }}</div>
                        @endforeach
                    </div>
                    @foreach ($times as $i => $time)
                        <div class="grid gap-2 mb-2" style="grid-template-columns: 96px repeat({{ count($availability['courts']) }}, 1fr);">
                            <div class="text-[12px] font-semibold flex items-center" style="color: var(--db-ink-soft);">
                                {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $time->startTime)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $time->endTime)->format('g:i A') }}
                            </div>
                            @foreach ($availability['courts'] as $courtAvailability)
                                @php
                                    $slot = $courtAvailability->slots[$i];
                                    $court = $courtAvailability->court;
                                    $key = "{$court->id}|{$slot->startTime}";
                                    $isSelected = isset($selected[$key]);
                                    $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                                    $bookable = $slot->status === $slotStatus::Available && ($slotStart->lte(\Illuminate\Support\Carbon::now()) || $slotStart->gte($bookableFrom));
                                    $statusKey = $isSelected ? 'selected' : $slot->status->value;
                                    $meta = $statusMeta[$statusKey] ?? $statusMeta['closed'];
                                @endphp
                                <div>
                                    @if ($bookable || $isSelected)
                                        <button type="button"
                                                wire:click="toggleSlot({{ $court->id }}, '{{ addslashes($court->name) }}', '{{ $slot->startTime }}', '{{ $slot->endTime }}')"
                                                class="w-full h-14 rounded-lg text-[13px] font-bold cursor-pointer transition-opacity hover:opacity-80"
                                                style="background: {{ $meta['bg'] }}; border: 1px solid {{ $meta['border'] }}; color: {{ $meta['text'] }};">
                                            {{ $meta['icon'] }} {{ $isSelected ? 'Selected' : $slot->status->label() }}
                                        </button>
                                    @elseif ($slot->holdExpiresAt)
                                        <div class="w-full h-14 rounded-lg text-[13px] font-bold flex flex-col items-center justify-center tabular-nums"
                                             style="background: {{ $meta['bg'] }}; border: 1px solid {{ $meta['border'] }}; color: {{ $meta['text'] }};"
                                             x-data="{
                                                 expiresAt: new Date('{{ $slot->holdExpiresAt }}').getTime(),
                                                 remaining: 0,
                                                 tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
                                                 get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
                                             }"
                                             x-init="tick(); setInterval(() => tick(), 1000)">
                                            <span class="text-[10px] font-semibold opacity-80">{{ $meta['icon'] }} In Progress</span>
                                            <span x-text="timeLabel"></span>
                                        </div>
                                    @else
                                        <div class="w-full h-14 rounded-lg text-[13px] font-bold flex items-center justify-center"
                                             style="background: {{ $meta['bg'] }}; border: 1px solid {{ $meta['border'] }}; color: {{ $meta['text'] }};">
                                            {{ $meta['icon'] }} {{ $slot->status->label() }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="rounded-2xl p-6 flex flex-col gap-4 sticky top-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                    <div class="text-lg font-bold" style="color: var(--db-ink);">Selected Slots</div>
                    @if (! empty($selected))
                        <div class="flex flex-col gap-2.5">
                            @foreach ($selected as $key => $s)
                                <div class="flex justify-between items-center p-3 rounded-lg" style="background: #EFF6FF; border: 1px solid #BFDBFE;">
                                    <div>
                                        <div class="text-sm font-bold" style="color: var(--db-ink);">{{ $s['court_name'] }}</div>
                                        <div class="text-[13px]" style="color: var(--db-ink-soft);">
                                            {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $s['start_time'])->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $s['end_time'])->format('g:i A') }}
                                        </div>
                                    </div>
                                    <div class="text-sm font-bold" style="color: var(--db-ink);">₱{{ number_format($slotPrices[$key] ?? 0, 0) }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="h-px" style="background: var(--db-border);"></div>
                        <div class="flex justify-between items-center">
                            <div class="text-[15px] font-semibold" style="color: var(--db-ink-soft);">Total</div>
                            <div class="text-2xl font-extrabold" style="color: var(--db-ink);">₱{{ number_format($totalPrice, 2) }}</div>
                        </div>
                        <button type="button" wire:click="startReview" class="text-[16px] font-bold py-3.5 rounded-lg text-center cursor-pointer" style="background: var(--db-accent); color: var(--db-accent-ink);">Continue Booking</button>
                    @else
                        <div class="text-sm text-center py-6 rounded-lg" style="color: var(--db-ink-faint); border: 1px dashed var(--db-border);">Select a time slot to begin your booking.</div>
                    @endif
                </div>
            </div>

            {{-- ============ Mobile: court tabs + vertical list + sticky bar (below lg) ============ --}}
            <div class="lg:hidden">
                <div class="flex gap-2 overflow-x-auto pb-1 mb-4">
                    @foreach ($availability['courts'] as $courtAvailability)
                        @php $isActiveCourt = $courtAvailability->court->id === $effectiveMobileCourt; @endphp
                        <button type="button" wire:click="selectMobileCourt({{ $courtAvailability->court->id }})"
                                class="px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap"
                                style="background: {{ $isActiveCourt ? 'var(--db-nav)' : 'var(--db-surface)' }}; color: {{ $isActiveCourt ? '#FFFFFF' : 'var(--db-ink-soft)' }}; border: 1px solid {{ $isActiveCourt ? 'var(--db-nav)' : 'var(--db-border)' }};">
                            {{ $courtAvailability->court->name }}
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-col gap-2.5 {{ ! empty($selected) ? 'pb-24' : '' }}">
                    @php $activeCourtAvailability = collect($availability['courts'])->first(fn ($ca) => $ca->court->id === $effectiveMobileCourt); @endphp
                    @foreach ($times as $i => $time)
                        @php
                            $slot = $activeCourtAvailability->slots[$i] ?? null;
                            if (! $slot) continue;
                            $court = $activeCourtAvailability->court;
                            $key = "{$court->id}|{$slot->startTime}";
                            $isSelected = isset($selected[$key]);
                            $slotStart = \Illuminate\Support\Carbon::parse($date.' '.$slot->startTime);
                            $bookable = $slot->status === $slotStatus::Available && ($slotStart->lte(\Illuminate\Support\Carbon::now()) || $slotStart->gte($bookableFrom));
                            $statusKey = $isSelected ? 'selected' : $slot->status->value;
                            $meta = $statusMeta[$statusKey] ?? $statusMeta['closed'];
                        @endphp
                        @if ($bookable || $isSelected)
                            <button type="button" wire:click="toggleSlot({{ $court->id }}, '{{ addslashes($court->name) }}', '{{ $slot->startTime }}', '{{ $slot->endTime }}')"
                                    class="flex justify-between items-center px-4 py-3.5 rounded-xl w-full cursor-pointer"
                                    style="border: 1px solid {{ $meta['border'] }}; background: {{ $isSelected ? '#EFF6FF' : 'var(--db-surface)' }};">
                                <span class="text-[15px] font-bold" style="color: var(--db-ink);">
                                    {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->startTime)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->endTime)->format('g:i A') }}
                                </span>
                                <span class="text-[13px] font-bold px-3 py-1.5 rounded-full" style="color: {{ $meta['text'] }}; background: {{ $meta['bg'] }};">
                                    {{ $meta['icon'] }} {{ $isSelected ? 'Selected' : $slot->status->label() }}
                                </span>
                            </button>
                        @elseif ($slot->holdExpiresAt)
                            <div class="flex justify-between items-center px-4 py-3.5 rounded-xl"
                                 style="border: 1px solid {{ $meta['border'] }}; background: var(--db-surface);"
                                 x-data="{
                                     expiresAt: new Date('{{ $slot->holdExpiresAt }}').getTime(),
                                     remaining: 0,
                                     tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
                                     get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
                                 }"
                                 x-init="tick(); setInterval(() => tick(), 1000)">
                                <span class="text-[15px] font-bold" style="color: var(--db-ink);">
                                    {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->startTime)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->endTime)->format('g:i A') }}
                                </span>
                                <span class="text-[13px] font-bold px-3 py-1.5 rounded-full tabular-nums" style="color: {{ $meta['text'] }}; background: {{ $meta['bg'] }};">
                                    {{ $meta['icon'] }} <span x-text="timeLabel"></span>
                                </span>
                            </div>
                        @else
                            <div class="flex justify-between items-center px-4 py-3.5 rounded-xl"
                                 style="border: 1px solid {{ $meta['border'] }}; background: var(--db-surface);">
                                <span class="text-[15px] font-bold" style="color: var(--db-ink);">
                                    {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->startTime)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $slot->endTime)->format('g:i A') }}
                                </span>
                                <span class="text-[13px] font-bold px-3 py-1.5 rounded-full" style="color: {{ $meta['text'] }}; background: {{ $meta['bg'] }};">
                                    {{ $meta['icon'] }} {{ $slot->status->label() }}
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            @if (! empty($selected))
                <div class="lg:hidden fixed bottom-0 left-0 right-0 px-5 py-4 flex flex-col gap-3" style="background: var(--db-surface); border-top: 1px solid var(--db-border); box-shadow: 0 -8px 24px rgba(15,23,42,0.08);">
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-semibold" style="color: var(--db-ink-soft);">{{ count($selected) }} slot{{ count($selected) === 1 ? '' : 's' }} selected</div>
                        <div class="text-xl font-extrabold" style="color: var(--db-ink);">₱{{ number_format($totalPrice, 2) }}</div>
                    </div>
                    <button type="button" wire:click="startReview" class="text-[16px] font-bold py-3.5 rounded-lg text-center" style="background: var(--db-accent); color: var(--db-accent-ink);">Continue Booking</button>
                </div>
            @endif
        @endif
    @endif
</div>
