@extends('layouts.app', ['title' => 'Review Order'])

@section('content')
    @php
        $lines = collect($items)->map(fn (array $item) => [
            'id' => $item['product']->id,
            'name' => $item['product']->name,
            'unit' => $item['product']->unit,
            'price' => (float) $item['product']->selling_price,
            'max' => $item['product']->stock_quantity,
            'qty' => $item['quantity'],
        ])->values();
    @endphp

    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Review Order</h1>

    {{-- Quantities are edited and lines removed right here, in the browser -
         the totals below follow along, and only what's still on the order is
         submitted. The server re-checks stock and prices at checkout anyway. --}}
    <div class="max-w-md"
         x-data="{
             lines: {{ \Illuminate\Support\Js::from($lines) }},
             discount: 0,
             amountPaid: null,
             get active() { return this.lines.filter(l => parseInt(l.qty) > 0); },
             get subtotal() { return this.active.reduce((sum, l) => sum + l.price * parseInt(l.qty), 0); },
             get total() { return Math.max(0, this.subtotal - (parseFloat(this.discount) || 0)); },
             get change() { return this.amountPaid === null || this.amountPaid === '' ? null : (parseFloat(this.amountPaid) || 0) - this.total; },
             remove(line) { this.lines = this.lines.filter(l => l !== line); },
             clamp(line) {
                 let q = parseInt(line.qty);
                 if (isNaN(q) || q < 1) q = 1;
                 line.qty = Math.min(q, line.max);
             },
             get addMoreUrl() {
                 const params = this.active.map(l => 'quantities[' + l.id + ']=' + parseInt(l.qty)).join('&');
                 return '{{ route('admin.store.pos.index') }}' + (params ? '?' + params : '');
             },
         }">
        <x-card class="space-y-3 text-sm mb-6">
            <p class="text-xs font-medium text-slate-500 uppercase" x-text="active.length + ' item' + (active.length === 1 ? '' : 's')"></p>

            <template x-for="line in lines" :key="line.id">
                <div class="flex items-center gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="text-slate-900 truncate" x-text="line.name"></div>
                        <div class="text-xs text-slate-500" x-text="'₱' + line.price.toFixed(2) + ' / ' + line.unit + ' · ' + line.max + ' in stock'"></div>
                    </div>
                    <input type="number" min="1" :max="line.max" x-model="line.qty" @change="clamp(line)"
                           class="w-16 rounded-lg border-slate-300 shadow-sm text-sm text-center focus:border-blue-500 focus:ring-blue-500">
                    <span class="w-20 text-right text-slate-700" x-text="'₱' + (line.price * (parseInt(line.qty) || 0)).toFixed(2)"></span>
                    <button type="button" @click="remove(line)" class="text-red-600 hover:text-red-700 text-lg leading-none px-1" aria-label="Remove item" title="Remove">&times;</button>
                </div>
            </template>

            <p x-show="lines.length === 0" x-cloak class="text-center text-slate-500 py-4">The order is empty.</p>

            <div class="flex justify-between border-t border-slate-100 pt-2 font-semibold">
                <span class="text-slate-700">Subtotal</span>
                <span class="text-slate-900" x-text="'₱' + subtotal.toFixed(2)"></span>
            </div>

            <a :href="addMoreUrl" class="block text-center text-blue-600 hover:text-blue-700 underline underline-offset-2">+ Add more items</a>
        </x-card>

        <x-card>
            <form method="POST" action="{{ route('admin.store.pos.checkout') }}" class="space-y-4">
                @csrf
                <template x-for="line in active" :key="line.id">
                    <input type="hidden" :name="'quantities[' + line.id + ']'" :value="parseInt(line.qty)">
                </template>

                <div>
                    <label for="discount" class="block text-sm font-medium text-slate-700">Discount (₱, optional)</label>
                    <input id="discount" name="discount" type="number" step="0.01" min="0" x-model="discount"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex justify-between text-sm font-semibold border-t border-slate-100 pt-3">
                    <span class="text-slate-700">Total</span>
                    <span class="text-slate-900" x-text="'₱' + total.toFixed(2)"></span>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-slate-700">Payment Method</label>
                    <select id="payment_method" name="payment_method" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount_paid" class="block text-sm font-medium text-slate-700">Amount Paid (₱)</label>
                    <input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" x-model="amountPaid" required autofocus
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex justify-between text-sm font-semibold" x-show="change !== null" x-cloak>
                    <span class="text-slate-700">Change</span>
                    <span :class="change < 0 ? 'text-red-600' : 'text-green-700'" x-text="'₱' + (change ?? 0).toFixed(2)"></span>
                </div>

                <x-button type="submit" class="w-full" x-bind:disabled="active.length === 0">Complete Sale</x-button>

                <a href="{{ route('admin.store.pos.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">
                    Start over
                </a>
            </form>
        </x-card>
    </div>
@endsection
