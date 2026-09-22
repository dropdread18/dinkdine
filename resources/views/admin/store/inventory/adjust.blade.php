@extends('layouts.app', ['title' => 'Adjust Stock'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-1">Adjust Stock</h1>
    <p class="text-sm text-slate-500 mb-4">{{ $product->name }} - currently {{ $product->stock_quantity }} {{ $product->unit }}.</p>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.store.inventory.adjust', $product) }}" class="space-y-4">
            @csrf

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Reason Type</label>
                <select id="type" name="type" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="quantity_change" class="block text-sm font-medium text-slate-700">Quantity Change</label>
                <p class="text-xs text-slate-500 mb-1">Positive to add (e.g. a physical count found more than recorded), negative to remove (e.g. <code>-3</code> for 3 damaged units).</p>
                <input id="quantity_change" name="quantity_change" type="number" value="{{ old('quantity_change') }}" required autofocus
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-slate-700">Notes (optional)</label>
                <input id="reason" name="reason" type="text" value="{{ old('reason') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <x-button type="submit" class="w-full">Save Adjustment</x-button>

            <a href="{{ route('admin.store.inventory.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
