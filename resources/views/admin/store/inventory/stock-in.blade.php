@extends('layouts.app', ['title' => 'Stock In'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-1">Stock In</h1>
    <p class="text-sm text-slate-500 mb-4">{{ $product->name }} - currently {{ $product->stock_quantity }} {{ $product->unit }}.</p>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.store.inventory.stock-in', $product) }}" class="space-y-4">
            @csrf

            <div>
                <label for="quantity" class="block text-sm font-medium text-slate-700">Quantity Received</label>
                <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-slate-700">Reason (optional)</label>
                <input id="reason" name="reason" type="text" placeholder="e.g. New delivery" value="{{ old('reason') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <x-button type="submit" class="w-full">Add Stock</x-button>

            <a href="{{ route('admin.store.inventory.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
