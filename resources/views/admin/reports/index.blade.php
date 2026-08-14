@extends('layouts.app', ['title' => 'Reports'])

@section('content')
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-xl font-semibold text-gray-900">Reports</h1>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-6 text-sm">
        <a href="{{ route('manage.reports.index', ['range' => 'today']) }}"
           class="px-3 py-1.5 rounded {{ $range === 'today' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">Today</a>
        <a href="{{ route('manage.reports.index', ['range' => 'week']) }}"
           class="px-3 py-1.5 rounded {{ $range === 'week' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">This Week</a>
        <a href="{{ route('manage.reports.index', ['range' => 'month']) }}"
           class="px-3 py-1.5 rounded {{ $range === 'month' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">This Month</a>

        <form method="GET" action="{{ route('manage.reports.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="range" value="custom">
            <input type="date" name="start" value="{{ $start->toDateString() }}" class="rounded border-gray-300 shadow-sm text-sm">
            <span class="text-gray-500">to</span>
            <input type="date" name="end" value="{{ $end->toDateString() }}" class="rounded border-gray-300 shadow-sm text-sm">
            <button type="submit" class="px-3 py-1.5 rounded {{ $range === 'custom' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700' }}">
                Custom
            </button>
        </form>
    </div>

    <p class="text-sm text-gray-500 mb-6">
        Showing {{ $start->format('M j, Y') }}
        @if (! $start->isSameDay($end))
            – {{ $end->format('M j, Y') }}
        @endif
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <div class="bg-white border rounded-lg p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Revenue</h2>
            <p class="text-2xl font-semibold text-gray-900">₱{{ number_format($revenue['total'], 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $revenue['count'] }} payment{{ $revenue['count'] === 1 ? '' : 's' }}</p>
        </div>

        <div class="bg-white border rounded-lg p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Bookings</h2>
            <p class="text-2xl font-semibold text-gray-900">{{ $bookingCounts['total'] }}</p>
            <p class="text-sm text-gray-500 mt-1">
                {{ $bookingCounts['confirmed'] }} confirmed &middot;
                {{ $bookingCounts['cancelled'] }} cancelled &middot;
                {{ $bookingCounts['completed'] }} completed &middot;
                {{ $bookingCounts['no_show'] }} no-show
            </p>
        </div>
    </div>

    <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Court Utilization</h2>
    <div class="overflow-x-auto mb-8">
        <table class="min-w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="text-left font-medium text-gray-500 pb-2 pr-4">Court</th>
                    <th class="text-left font-medium text-gray-500 pb-2 pr-4">Booked Hours</th>
                    <th class="text-left font-medium text-gray-500 pb-2 pr-4">Possible Hours</th>
                    <th class="text-left font-medium text-gray-500 pb-2">Utilization</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($utilization as $row)
                    <tr class="border-t">
                        <td class="py-2 pr-4 text-gray-900">{{ $row['court']->name }}</td>
                        <td class="py-2 pr-4 text-gray-600">{{ $row['booked_hours'] }}</td>
                        <td class="py-2 pr-4 text-gray-600">{{ $row['possible_hours'] }}</td>
                        <td class="py-2 text-gray-600">
                            {{ $row['utilization_percent'] !== null ? $row['utilization_percent'].'%' : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Export</h2>
    <div class="flex gap-3 text-sm">
        <a href="{{ route('manage.reports.export-bookings', request()->query()) }}" class="underline text-gray-700">Export Bookings (CSV)</a>
        <a href="{{ route('manage.reports.export-payments', request()->query()) }}" class="underline text-gray-700">Export Payments (CSV)</a>
    </div>
@endsection
