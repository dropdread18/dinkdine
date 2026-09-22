@php $category = $category ?? null; @endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $category?->name) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="description" class="block text-sm font-medium text-slate-700">Description (optional)</label>
    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $category?->description) }}</textarea>
</div>
