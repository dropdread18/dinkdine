@extends('layouts.app', ['title' => 'Admin Dashboard'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900">Admin Dashboard</h1>
    <p class="text-gray-600 mt-2">
        Signed in as {{ auth()->user()->name }} ({{ auth()->user()->role->label() }}).
    </p>
    <p class="text-gray-500 text-sm mt-4">
        Courts, bookings, customers, staff, payments, reports, and settings are not built yet
        (see <code>Requirements.md</code> §35, §43 for what's planned).
    </p>
@endsection
