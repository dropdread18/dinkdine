@extends('layouts.app', ['title' => 'Reports'])

@section('content')
    <x-page-header title="Reports" />

    <div class="flex flex-wrap items-center gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-2">
        <a href="{{ route('manage.reports.index', ['range' => 'today']) }}"
           class="px-3 py-1.5 rounded-lg font-medium {{ $range === 'today' ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Today</a>
        <a href="{{ route('manage.reports.index', ['range' => 'week']) }}"
           class="px-3 py-1.5 rounded-lg font-medium {{ $range === 'week' ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">This Week</a>
        <a href="{{ route('manage.reports.index', ['range' => 'month']) }}"
           class="px-3 py-1.5 rounded-lg font-medium {{ $range === 'month' ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">This Month</a>

        <form method="GET" action="{{ route('manage.reports.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="range" value="custom">
            <input type="date" name="start" value="{{ $start->toDateString() }}" class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            <span class="text-slate-500">to</span>
            <input type="date" name="end" value="{{ $end->toDateString() }}" class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            <button type="submit" class="px-3 py-1.5 rounded-lg font-medium {{ $range === 'custom' ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                Custom
            </button>
        </form>
    </div>

    <p class="text-sm text-slate-500 mb-6">
        Showing {{ $start->format('M j, Y') }}
        @if (! $start->isSameDay($end))
            – {{ $end->format('M j, Y') }}
        @endif
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        <x-card>
            <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Revenue</h2>
            <p class="text-3xl font-semibold text-slate-900 tracking-tight">₱{{ number_format($revenue['total'], 2) }}</p>
            <p class="text-sm text-slate-500 mt-1">{{ $revenue['count'] }} payment{{ $revenue['count'] === 1 ? '' : 's' }}</p>
        </x-card>

        <x-card>
            <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Bookings</h2>
            <p class="text-3xl font-semibold text-slate-900 tracking-tight">{{ $bookingCounts['total'] }}</p>
            <p class="text-sm text-slate-500 mt-1">
                {{ $bookingCounts['confirmed'] }} confirmed &middot;
                {{ $bookingCounts['cancelled'] }} cancelled &middot;
                {{ $bookingCounts['completed'] }} completed &middot;
                {{ $bookingCounts['no_show'] }} no-show
            </p>
        </x-card>
    </div>

    <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Court Utilization</h2>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white mb-8">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-slate-50">
                    <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Court</th>
                    <th class="text-left font-medium text-slate-500 py-3 pr-4">Booked Hours</th>
                    <th class="text-left font-medium text-slate-500 py-3 pr-4">Possible Hours</th>
                    <th class="text-left font-medium text-slate-500 py-3 pr-4">Utilization</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($utilization as $row)
                    <tr class="border-t border-slate-100">
                        <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $row['court']->name }}</td>
                        <td class="py-3 pr-4 text-slate-600">{{ $row['booked_hours'] }}</td>
                        <td class="py-3 pr-4 text-slate-600">{{ $row['possible_hours'] }}</td>
                        <td class="py-3 pr-4 text-slate-600">
                            {{ $row['utilization_percent'] !== null ? $row['utilization_percent'].'%' : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Export</h2>
    <div class="flex gap-4 text-sm">
        <a href="{{ route('manage.reports.export-bookings', request()->query()) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Export Bookings (CSV)</a>
        <a href="{{ route('manage.reports.export-payments', request()->query()) }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Export Payments (CSV)</a>
    </div>
@endsection
