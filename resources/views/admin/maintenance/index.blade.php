@extends('layouts.app', ['title' => 'Court Maintenance'])

@section('content')
    <x-page-header title="Court Maintenance">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.maintenance.create') }}">Schedule Maintenance</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($maintenancePeriods->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No maintenance windows scheduled.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Court</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Starts</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Ends</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Reason</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($maintenancePeriods as $period)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $period->court->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $period->starts_at->format('M j, Y g:ia') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $period->ends_at->format('M j, Y g:ia') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $period->reason ?: '—' }}</td>
                            <td class="py-3 pr-4">
                                @if ($period->ends_at->isPast())
                                    <x-badge color="slate">Past</x-badge>
                                @elseif ($period->starts_at->isPast())
                                    <x-badge color="amber">Ongoing</x-badge>
                                @else
                                    <x-badge color="blue">Upcoming</x-badge>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right space-x-3">
                                <a href="{{ route('admin.maintenance.edit', $period) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.maintenance.destroy', $period) }}" class="inline">
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
