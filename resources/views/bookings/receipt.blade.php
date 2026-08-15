<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Receipt PB-{{ $booking->id }} - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-mint text-slate-900 py-10 print:bg-white print:py-0">
        @php
            $statusColor = match ($booking->status) {
                \App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Completed => 'green',
                \App\Enums\BookingStatus::Pending => 'amber',
                default => 'slate',
            };
        @endphp

        <div class="max-w-lg mx-auto px-4 print:px-0 print:max-w-none">
            <div class="flex justify-end gap-2 mb-4 print:hidden">
                <x-button tag="a" href="{{ route('bookings.show', $booking) }}" variant="secondary">Back</x-button>
                <x-button type="button" onclick="window.print()">Print Receipt</x-button>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 print:border-0 print:shadow-none print:rounded-none">
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-100">
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">{{ \App\Models\Setting::get('facility_name', config('app.name')) }}</h1>
                        @if (\App\Models\Setting::get('facility_address'))
                            <p class="text-sm text-slate-500">{{ \App\Models\Setting::get('facility_address') }}</p>
                        @endif
                        @if (\App\Models\Setting::get('facility_phone'))
                            <p class="text-sm text-slate-500">{{ \App\Models\Setting::get('facility_phone') }}</p>
                        @endif
                    </div>
                    <x-badge :color="$statusColor">{{ $booking->status->label() }}</x-badge>
                </div>

                <h2 class="text-base font-semibold text-slate-900 mb-4">Booking Receipt</h2>

                <dl class="text-sm space-y-2 mb-6">
                    <div class="flex justify-between"><dt class="text-slate-500">Booking #</dt><dd class="font-medium">PB-{{ $booking->id }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Customer</dt><dd class="font-medium">{{ $booking->user->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Court</dt><dd class="font-medium">{{ $booking->court->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Date</dt><dd class="font-medium">{{ $booking->booking_date->format('F j, Y') }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Time</dt>
                        <dd class="font-medium">
                            {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }} –
                            {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                        </dd>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-slate-100"><dt class="text-slate-500">Amount</dt><dd class="font-semibold">₱{{ number_format($booking->price, 2) }}</dd></div>
                </dl>

                @if ($booking->payment)
                    <h2 class="text-base font-semibold text-slate-900 mb-3">Payment</h2>
                    <dl class="text-sm space-y-2 mb-6">
                        <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ $booking->payment->status->label() }}</dd></div>
                        @if ($booking->payment->reference_number)
                            <div class="flex justify-between"><dt class="text-slate-500">Reference #</dt><dd class="font-medium font-mono">{{ $booking->payment->reference_number }}</dd></div>
                        @endif
                    </dl>
                @endif

                <p class="text-xs text-slate-500 pt-4 border-t border-slate-100">
                    Please bring this receipt {{ $booking->payment?->reference_number ? '(and the reference number above) ' : '' }}with you when you arrive.
                </p>
            </div>
        </div>
    </body>
</html>
