@extends('layouts.app', ['title' => 'Open Play'])

@section('content')
    <x-page-header title="Open Play">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.open-play.create') }}">Schedule Open Play</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($sessions->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No Open Play sessions scheduled.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Date</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Time</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Notes</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Registration</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        @php
                            $endsAt = $session->session_date->copy()->setTimeFromTimeString($session->end_time);
                            $startsAt = $session->session_date->copy()->setTimeFromTimeString($session->start_time);
                        @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $session->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $session->session_date->format('M j, Y') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $startsAt->format('g:ia') }} – {{ $endsAt->format('g:ia') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $session->notes ?: '—' }}</td>
                            <td class="py-3 pr-4">
                                @if ($session->registration_link)
                                    <a href="{{ $session->registration_link }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Link set</a>
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
                                <a href="{{ route('admin.open-play.edit', $session) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.open-play.destroy', $session) }}" class="inline">
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
