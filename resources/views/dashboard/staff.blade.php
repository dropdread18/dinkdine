@extends('layouts.app', ['title' => 'Staff Dashboard'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900">Staff Dashboard</h1>
    <p class="text-gray-600 mt-2">
        Signed in as {{ auth()->user()->name }} ({{ auth()->user()->role->label() }}).
    </p>
    <p class="text-gray-500 text-sm mt-4">
        Today's bookings, walk-ins, and check-in are not built yet
        (see <code>Requirements.md</code> §19, §24 for what's planned).
    </p>
@endsection
