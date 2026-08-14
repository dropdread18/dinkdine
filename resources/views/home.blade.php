@extends('layouts.app', ['title' => 'Home'])

@section('content')
    @auth
        <h1 class="text-xl font-semibold text-gray-900">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="text-gray-600 mt-2">
            You're signed in as {{ auth()->user()->role->label() }}.
            @if (auth()->user()->isAdmin())
                Head to your <a href="{{ route('admin.dashboard') }}" class="underline">dashboard</a>.
            @elseif (auth()->user()->isStaff())
                Head to your <a href="{{ route('staff.dashboard') }}" class="underline">dashboard</a>.
            @else
                <a href="{{ route('bookings.index') }}" class="underline">Book a court</a>.
            @endif
        </p>
    @else
        <h1 class="text-xl font-semibold text-gray-900">{{ config('app.name') }}</h1>
        <p class="text-gray-600 mt-2">
            See real-time court availability and book online.
            <a href="{{ route('register') }}" class="underline">Create an account</a> or
            <a href="{{ route('login') }}" class="underline">log in</a> to get started.
        </p>
    @endauth
@endsection
