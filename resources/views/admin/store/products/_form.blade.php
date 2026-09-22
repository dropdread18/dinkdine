@php $product = $product ?? null; @endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $product?->name) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="category_id" class="block text-sm font-medium text-slate-700">Category</label>
    <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((int) old('category_id', $product?->category_id) === $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="sku" class="block text-sm font-medium text-slate-700">SKU</label>
    <input id="sku" name="sku" type="text" value="{{ old('sku', $product?->sku) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm font-mono focus:border-blue-500 focus:ring-blue-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="cost_price" class="block text-sm font-medium text-slate-700">Cost Price (₱)</label>
        <input id="cost_price" name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price', $product?->cost_price) }}" required
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="selling_price" class="block text-sm font-medium text-slate-700">Selling Price (₱)</label>
        <input id="selling_price" name="selling_price" type="number" step="0.01" min="0" value="{{ old('selling_price', $product?->selling_price) }}" required
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div>
    <label for="unit" class="block text-sm font-medium text-slate-700">Unit</label>
    <input id="unit" name="unit" type="text" placeholder="e.g. cup, piece, bottle" value="{{ old('unit', $product?->unit) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="stock_quantity" class="block text-sm font-medium text-slate-700">Stock Quantity</label>
        <p class="text-xs text-slate-500 mb-1">Initial count only - Phase 3 adds tracked stock movements.</p>
        <input id="stock_quantity" name="stock_quantity" type="number" min="0" value="{{ old('stock_quantity', $product?->stock_quantity) }}" required
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="minimum_stock" class="block text-sm font-medium text-slate-700">Minimum Stock</label>
        <p class="text-xs text-slate-500 mb-1">Below this, the product shows as Low Stock.</p>
        <input id="minimum_stock" name="minimum_stock" type="number" min="0" value="{{ old('minimum_stock', $product?->minimum_stock) }}" required
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div>
    <label for="description" class="block text-sm font-medium text-slate-700">Description (optional)</label>
    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $product?->description) }}</textarea>
</div>
