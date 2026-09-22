@extends('layouts.app', ['title' => 'Store Inventory'])

@section('content')
    <x-page-header title="Inventory" />

    @include('partials.store-subnav')

    <form method="GET" action="{{ route('admin.store.inventory.index') }}" class="flex flex-wrap items-center gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name or SKU"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-blue-500 focus:ring-blue-500">

        <label class="flex items-center gap-2 text-slate-600">
            <input type="checkbox" name="low_stock" value="1" @checked($lowOnly) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            Needs restocking only
        </label>

        <x-button type="submit">Filter</x-button>
        <x-button tag="a" href="{{ route('admin.store.inventory.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($products->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No products match these filters.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Category</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Stock</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Minimum</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $product->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $product->category?->name ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $product->stock_quantity }} {{ $product->unit }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $product->minimum_stock }}</td>
                            <td class="py-3 pr-4">
                                @if ($product->isOutOfStock())
                                    <x-badge color="red">Out of Stock</x-badge>
                                @elseif ($product->isLowStock())
                                    <x-badge color="amber">Low Stock</x-badge>
                                @else
                                    <x-badge color="green">OK</x-badge>
                                @endif
                            </td>
                            <td class="py-3 pr-4 space-x-3 whitespace-nowrap text-right">
                                <a href="{{ route('admin.store.inventory.stock-in-form', $product) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Stock In</a>
                                <a href="{{ route('admin.store.inventory.adjust-form', $product) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Adjust</a>
                                <a href="{{ route('admin.store.inventory.history', $product) }}" class="text-slate-600 hover:text-slate-900 underline underline-offset-2">History</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
