@extends('layouts.app', ['title' => 'Review Order'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Review Order</h1>

    <x-card class="max-w-md space-y-2 text-sm mb-6">
        <p class="text-xs font-medium text-slate-500 uppercase mb-1">{{ count($items) }} item{{ count($items) === 1 ? '' : 's' }}</p>
        <div class="space-y-1.5">
            @foreach ($items as $item)
                <div class="flex justify-between">
                    <span class="text-slate-900">{{ $item['quantity'] }} &times; {{ $item['product']->name }}</span>
                    <span class="text-slate-500">₱{{ number_format($item['product']->selling_price * $item['quantity'], 2) }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between border-t border-slate-100 pt-2 mt-2 font-semibold">
            <span class="text-slate-700">Subtotal</span>
            <span class="text-slate-900">₱{{ number_format($subtotal, 2) }}</span>
        </div>
    </x-card>

    <x-card class="max-w-md"
            x-data="{
                subtotal: {{ (float) $subtotal }},
                discount: 0,
                amountPaid: null,
                get total() { return Math.max(0, this.subtotal - (parseFloat(this.discount) || 0)); },
                get change() { return this.amountPaid === null || this.amountPaid === '' ? null : (parseFloat(this.amountPaid) || 0) - this.total; },
            }">
        <form method="POST" action="{{ route('admin.store.pos.checkout') }}" class="space-y-4">
            @csrf
            @foreach ($quantities as $productId => $quantity)
                <input type="hidden" name="quantities[{{ $productId }}]" value="{{ $quantity }}">
            @endforeach

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

            <x-button type="submit" class="w-full">Complete Sale</x-button>

            <a href="{{ route('admin.store.pos.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">
                Choose different products
            </a>
        </form>
    </x-card>
@endsection
