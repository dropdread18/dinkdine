@extends('layouts.app', ['title' => 'Admin Dashboard'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
    <p class="text-slate-500 mt-1 mb-8 text-sm">Here's a quick way to get where you need to go.</p>

    @php
        $links = [
            ['label' => 'Courts', 'route' => 'admin.courts.index', 'description' => 'Manage court listings and status'],
            ['label' => 'Maintenance', 'route' => 'admin.maintenance.index', 'description' => 'Schedule court maintenance windows'],
            ['label' => 'Bookings', 'route' => 'manage.bookings.index', 'description' => 'View, cancel, or reschedule bookings'],
            ['label' => 'Payments', 'route' => 'manage.payments.index', 'description' => 'Confirm payments and process refunds'],
            ['label' => 'Reports', 'route' => 'manage.reports.index', 'description' => 'Revenue, bookings, and utilization'],
            ['label' => 'Settings', 'route' => 'manage.settings.index', 'description' => 'Facility and booking-rule settings'],
            ['label' => 'Customers', 'route' => 'admin.customers.index', 'description' => 'Search customers and view profiles'],
            ['label' => 'Staff', 'route' => 'admin.staff.index', 'description' => 'Manage staff accounts'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}"
               class="block bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:border-teal-300 hover:shadow-md transition-all">
                <h2 class="font-semibold text-slate-900">{{ $link['label'] }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $link['description'] }}</p>
            </a>
        @endforeach
    </div>
@endsection
