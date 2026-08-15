@php $court = $court ?? null; @endphp

<div>
    <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $court?->name) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="court_number" class="block text-sm font-medium text-slate-700">Court Number</label>
    <input id="court_number" name="court_number" type="number" min="1" value="{{ old('court_number', $court?->court_number) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="hourly_rate" class="block text-sm font-medium text-slate-700">Hourly Rate (₱)</label>
    <input id="hourly_rate" name="hourly_rate" type="number" step="0.01" min="0" value="{{ old('hourly_rate', $court?->hourly_rate) }}" required
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
    <select id="status" name="status" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
        @foreach (\App\Enums\CourtStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(old('status', $court?->status?->value) === $status->value)>
                {{ $status->label() }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <label for="court_type" class="block text-sm font-medium text-slate-700">Court Type (optional)</label>
    <input id="court_type" name="court_type" type="text" value="{{ old('court_type', $court?->court_type) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="location" class="block text-sm font-medium text-slate-700">Location (optional)</label>
    <input id="location" name="location" type="text" value="{{ old('location', $court?->location) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="description" class="block text-sm font-medium text-slate-700">Description (optional)</label>
    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $court?->description) }}</textarea>
</div>

<div>
    <label for="sort_order" class="block text-sm font-medium text-slate-700">Sort Order (optional)</label>
    <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $court?->sort_order) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="image" class="block text-sm font-medium text-slate-700">Image (optional)</label>
    @if ($court?->image)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($court->image) }}" alt="{{ $court->name }}" class="w-24 h-24 object-cover rounded-lg mt-1 mb-2 border border-slate-200">
    @endif
    <input id="image" name="image" type="file" accept="image/*" class="mt-1 block w-full text-sm">
</div>
