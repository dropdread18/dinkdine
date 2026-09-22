@extends('layouts.app', ['title' => 'Store Products'])

@section('content')
    <x-page-header title="Products">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.store.products.create') }}">New Product</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.store-subnav')

    <form method="GET" action="{{ route('admin.store.products.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name or SKU"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-blue-500 focus:ring-blue-500">

        <select name="category_id" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="active" @selected($status === 'active')>Active</option>
            <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            <option value="" @selected($status === '')>Any Status</option>
        </select>

        <x-button type="submit">Filter</x-button>
        <x-button tag="a" href="{{ route('admin.store.products.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($products->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No products match these filters.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">SKU</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Category</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Cost</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Price</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Stock</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $product->name }}</td>
                            <td class="py-3 pr-4 text-slate-600 font-mono text-xs">{{ $product->sku }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $product->category?->name ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">₱{{ number_format($product->cost_price, 2) }}</td>
                            <td class="py-3 pr-4 text-slate-600">₱{{ number_format($product->selling_price, 2) }}</td>
                            <td class="py-3 pr-4 text-slate-600">
                                {{ $product->stock_quantity }} {{ $product->unit }}
                                @if ($product->isOutOfStock())
                                    <x-badge color="red" class="ml-1">Out of Stock</x-badge>
                                @elseif ($product->isLowStock())
                                    <x-badge color="amber" class="ml-1">Low Stock</x-badge>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$product->is_active ? 'green' : 'slate'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 space-x-3 whitespace-nowrap text-right">
                                <a href="{{ route('admin.store.products.edit', $product) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.store.products.toggle-active', $product) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="{{ $product->is_active ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700' }} underline underline-offset-2">
                                        {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
