@extends('layouts.app', ['title' => 'Court Maintenance'])

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Court Maintenance</h1>
        <a href="{{ route('admin.maintenance.create') }}" class="bg-gray-900 text-white rounded px-3 py-2 text-sm font-medium">
            Schedule Maintenance
        </a>
    </div>

    @if ($maintenancePeriods->isEmpty())
        <p class="text-gray-500">No maintenance windows scheduled.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Starts</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Ends</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Reason</th>
                        <th class="text-left font-medium text-gray-500 pb-2 pr-4">Status</th>
                        <th class="text-left font-medium text-gray-500 pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($maintenancePeriods as $period)
                        <tr class="border-t">
                            <td class="py-2 pr-4 text-gray-900">{{ $period->court->name }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $period->starts_at->format('M j, Y g:ia') }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $period->ends_at->format('M j, Y g:ia') }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $period->reason ?: '—' }}</td>
                            <td class="py-2 pr-4">
                                @if ($period->ends_at->isPast())
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-gray-100 text-gray-600">Past</span>
                                @elseif ($period->starts_at->isPast())
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-amber-100 text-amber-800">Ongoing</span>
                                @else
                                    <span class="inline-block rounded px-2 py-0.5 text-xs bg-blue-100 text-blue-800">Upcoming</span>
                                @endif
                            </td>
                            <td class="py-2 text-right space-x-2">
                                <a href="{{ route('admin.maintenance.edit', $period) }}" class="text-gray-700 underline">Edit</a>
                                <form method="POST" action="{{ route('admin.maintenance.destroy', $period) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-700 underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
