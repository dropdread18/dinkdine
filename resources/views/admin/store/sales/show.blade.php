<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.favicon')
        <title>Receipt {{ $sale->sale_number }} - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-mint text-slate-900 py-10 print:bg-white print:py-0">
        @php
            $statusColor = match ($sale->status) {
                \App\Enums\StoreSaleStatus::Completed => 'green',
                \App\Enums\StoreSaleStatus::Refunded => 'amber',
                \App\Enums\StoreSaleStatus::Voided => 'slate',
            };
        @endphp

        <div class="max-w-lg mx-auto px-4 print:px-0 print:max-w-none">
            <div class="flex justify-between gap-2 mb-4 print:hidden">
                {{-- Sales History is admin-only (handoff spec section 35) - a
                     staff cashier viewing their own just-completed receipt
                     has nowhere to "go back" to, so they only get New Sale. --}}
                @if (auth()->user()->isAdmin())
                    <x-button tag="a" href="{{ route('admin.store.sales.index') }}" variant="ghost">Back to Sales</x-button>
                @else
                    <span></span>
                @endif
                <div class="flex gap-2">
                    <x-button tag="a" href="{{ route('admin.store.pos.index') }}" variant="secondary">New Sale</x-button>
                    <x-button type="button" onclick="window.print()">Print Receipt</x-button>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 print:border-0 print:shadow-none print:rounded-none">
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-100">
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">{{ \App\Models\Setting::get('facility_name', config('app.name')) }}</h1>
                        @if (\App\Models\Setting::get('facility_address'))
                            <p class="text-sm text-slate-500">{{ \App\Models\Setting::get('facility_address') }}</p>
                        @endif
                    </div>
                    <x-badge :color="$statusColor">{{ $sale->status->label() }}</x-badge>
                </div>

                <h2 class="text-base font-semibold text-slate-900 mb-4">Store Receipt</h2>

                <dl class="text-sm space-y-2 mb-6">
                    <div class="flex justify-between"><dt class="text-slate-500">Sale #</dt><dd class="font-medium font-mono">{{ $sale->sale_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Date</dt><dd class="font-medium">{{ $sale->created_at->format('F j, Y g:i A') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Cashier</dt><dd class="font-medium">{{ $sale->user?->name ?: '—' }}</dd></div>
                </dl>

                <div class="space-y-1.5 text-sm mb-4">
                    @foreach ($sale->items as $item)
                        <div class="flex justify-between">
                            <span class="text-slate-900">{{ $item->quantity }} &times; {{ $item->product_name }}</span>
                            <span class="text-slate-500">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <dl class="text-sm space-y-2 mb-6 border-t border-slate-100 pt-3">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-medium">₱{{ number_format($sale->subtotal, 2) }}</dd></div>
                    @if ($sale->discount > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="font-medium">-₱{{ number_format($sale->discount, 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between text-base font-semibold"><dt class="text-slate-700">Total</dt><dd>₱{{ number_format($sale->total, 2) }}</dd></div>
                    <div class="flex justify-between pt-2 border-t border-slate-100"><dt class="text-slate-500">{{ $sale->payment_method->label() }} Received</dt><dd class="font-medium">₱{{ number_format($sale->amount_paid, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Change</dt><dd class="font-medium">₱{{ number_format($sale->change_amount, 2) }}</dd></div>
                </dl>

                <p class="text-xs text-slate-500 pt-4 border-t border-slate-100">Thank you!</p>
            </div>
        </div>
    </body>
</html>
