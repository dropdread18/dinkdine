@php
    $tournament = $tournament ?? null;
    $date = $date ?? null;
@endphp

<div>
    <label for="court_id" class="block text-sm font-medium text-slate-700">Court</label>
    <select id="court_id" name="court_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">Select a court</option>
        @foreach ($courts as $court)
            <option value="{{ $court->id }}" @selected((int) old('court_id', $tournament?->court_id) === $court->id)>
                {{ $court->name }} ({{ $court->status->label() }})
            </option>
        @endforeach
    </select>
</div>

<div>
    <label for="tournament_name" class="block text-sm font-medium text-slate-700">Tournament Name</label>
    <input id="tournament_name" name="tournament_name" type="text" required
           value="{{ old('tournament_name', $tournament?->tournament_name) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="session_date" class="block text-sm font-medium text-slate-700">Date</label>
    <input id="session_date" name="session_date" type="date" required
           value="{{ old('session_date', $tournament?->session_date?->format('Y-m-d') ?? $date) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="start_time" class="block text-sm font-medium text-slate-700">Starts</label>
        <input id="start_time" name="start_time" type="time" step="1" required
               value="{{ old('start_time', $tournament?->start_time) }}"
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="end_time" class="block text-sm font-medium text-slate-700">Ends</label>
        <input id="end_time" name="end_time" type="time" step="1" required
               value="{{ old('end_time', $tournament?->end_time) }}"
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div>
    <label for="notes" class="block text-sm font-medium text-slate-700">Notes (optional)</label>
    <input id="notes" name="notes" type="text" value="{{ old('notes', $tournament?->notes) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div>
    <label for="reclub_link" class="block text-sm font-medium text-slate-700">Reclub Link (optional)</label>
    <p class="text-xs text-slate-500 mb-1">For your own reference only - never shown to customers.</p>
    <input id="reclub_link" name="reclub_link" type="url" placeholder="https://reclub.co/..."
           value="{{ old('reclub_link', $tournament?->reclub_link) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>
