@extends('layouts.app', ['title' => 'Tournaments'])

@section('content')
    <x-page-header title="Tournaments">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.tournaments.create') }}">Schedule Tournament</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($tournaments->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No Tournaments scheduled.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Tournament</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Date</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Notes</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Reclub Link</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tournaments as $tournament)
                        @php
                            $endsAt = $tournament->session_date->copy()->setTimeFromTimeString($tournament->end_time);
                            $startsAt = $tournament->session_date->copy()->setTimeFromTimeString($tournament->start_time);
                        @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $tournament->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-900">{{ $tournament->tournament_name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $tournament->session_date->format('M j, Y') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $startsAt->format('g:ia') }} – {{ $endsAt->format('g:ia') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $tournament->notes ?: '—' }}</td>
                            <td class="py-3 pr-4">
                                @if ($tournament->reclub_link)
                                    <a href="{{ $tournament->reclub_link }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Link set</a>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                @if ($endsAt->isPast())
                                    <x-badge color="slate">Past</x-badge>
                                @elseif ($startsAt->isPast())
                                    <x-badge color="amber">Ongoing</x-badge>
                                @else
                                    <x-badge color="blue">Upcoming</x-badge>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right space-x-3">
                                <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.tournaments.destroy', $tournament) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 underline underline-offset-2">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
