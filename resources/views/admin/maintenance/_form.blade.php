@php $maintenance = $maintenance ?? null; @endphp

<div>
    <label for="court_id" class="block text-sm font-medium text-gray-700">Court</label>
    <select id="court_id" name="court_id" required class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        <option value="">Select a court</option>
        @foreach ($courts as $court)
            <option value="{{ $court->id }}" @selected((int) old('court_id', $maintenance?->court_id) === $court->id)>
                {{ $court->name }} ({{ $court->status->label() }})
            </option>
        @endforeach
    </select>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="starts_at" class="block text-sm font-medium text-gray-700">Starts</label>
        <input id="starts_at" name="starts_at" type="datetime-local" required
               value="{{ old('starts_at', $maintenance?->starts_at?->format('Y-m-d\TH:i')) }}"
               class="mt-1 block w-full rounded border-gray-300 shadow-sm">
    </div>

    <div>
        <label for="ends_at" class="block text-sm font-medium text-gray-700">Ends</label>
        <input id="ends_at" name="ends_at" type="datetime-local" required
               value="{{ old('ends_at', $maintenance?->ends_at?->format('Y-m-d\TH:i')) }}"
               class="mt-1 block w-full rounded border-gray-300 shadow-sm">
    </div>
</div>

<div>
    <label for="reason" class="block text-sm font-medium text-gray-700">Reason (optional)</label>
    <input id="reason" name="reason" type="text" value="{{ old('reason', $maintenance?->reason) }}"
           class="mt-1 block w-full rounded border-gray-300 shadow-sm">
</div>
