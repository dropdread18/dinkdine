{{-- Expects: $booking (a Pending booking with a still-future hold_expires_at) --}}
<x-card class="max-w-sm text-sm mt-4"
        x-data="{
            expiresAt: new Date('{{ $booking->hold_expires_at->toIso8601String() }}').getTime(),
            remaining: 0,
            tick() { this.remaining = Math.max(0, Math.floor((this.expiresAt - Date.now()) / 1000)); },
            get timeLabel() { return Math.floor(this.remaining / 60) + ':' + String(this.remaining % 60).padStart(2, '0'); },
        }"
        x-init="tick(); setInterval(() => tick(), 1000)">
    <div class="flex justify-between items-center mb-3">
        <h2 class="font-medium text-slate-900">Payment Required</h2>
        <span class="font-mono font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1 tabular-nums" x-text="timeLabel"></span>
    </div>

    <p class="text-slate-500 mb-3">This slot is held until the timer above runs out. Enter your payment reference number to confirm it.</p>

    <form method="POST" action="{{ route('bookings.continue-payment', $booking) }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <div>
            <label for="reference_number" class="block text-xs font-medium text-slate-700 mb-1">Payment Reference Number <span class="text-red-600">*</span></label>
            <input id="reference_number" name="reference_number" type="text" placeholder="e.g. GCASH-REF-88231" required
                   value="{{ old('reference_number') }}"
                   class="block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="payment_proof" class="block text-xs font-medium text-slate-700 mb-1">Upload a Screenshot of Your Receipt (optional)</label>
            <input id="payment_proof" name="payment_proof" type="file" accept="image/*"
                   class="block w-full text-sm text-slate-500 cursor-pointer file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-bold file:text-white">
        </div>

        @error('reference_number')
            <p class="text-red-600 text-xs">{{ $message }}</p>
        @enderror

        <x-button type="submit" class="w-full justify-center">Confirm Payment</x-button>
    </form>
</x-card>
