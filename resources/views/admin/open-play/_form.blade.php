@php
    $session = $session ?? null;
    $date = $date ?? null;
@endphp

@if ($session)
    <div>
        <label for="court_id" class="block text-sm font-medium text-slate-700">Court</label>
        <select id="court_id" name="court_id" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select a court</option>
            @foreach ($courts as $court)
                <option value="{{ $court->id }}" @selected((int) old('court_id', $session->court_id) === $court->id)>
                    {{ $court->name }} ({{ $court->status->label() }})
                </option>
            @endforeach
        </select>
    </div>
@else
    <div>
        <label class="block text-sm font-medium text-slate-700">Courts</label>
        <div class="mt-1 space-y-2">
            @foreach ($courts as $court)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="court_ids[]" value="{{ $court->id }}"
                           @checked(in_array($court->id, old('court_ids', []), true))
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    {{ $court->name }} ({{ $court->status->label() }})
                </label>
            @endforeach
        </div>
        <p class="text-xs text-slate-500 mt-1">Select one or more courts - a separate Open Play session is scheduled for each, at the same date and time.</p>
    </div>
@endif

<div>
    <label for="session_date" class="block text-sm font-medium text-slate-700">Date</label>
    <input id="session_date" name="session_date" type="date" required
           value="{{ old('session_date', $session?->session_date?->format('Y-m-d') ?? $date) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="start_time" class="block text-sm font-medium text-slate-700">Starts</label>
        <input id="start_time" name="start_time" type="time" step="1" required
               value="{{ old('start_time', $session?->start_time) }}"
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="end_time" class="block text-sm font-medium text-slate-700">Ends</label>
        <input id="end_time" name="end_time" type="time" step="1" required
               value="{{ old('end_time', $session?->end_time) }}"
               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div>
    <label for="notes" class="block text-sm font-medium text-slate-700">Notes (optional)</label>
    <input id="notes" name="notes" type="text" value="{{ old('notes', $session?->notes) }}"
           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
</div>
