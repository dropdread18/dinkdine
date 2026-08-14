@extends('layouts.app', ['title' => 'Home'])

@section('content')
    <x-card class="max-w-xl">
        @auth
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-slate-600 mt-2 text-sm leading-relaxed">
                You're signed in as {{ auth()->user()->role->label() }}.
                @if (auth()->user()->isAdmin())
                    Head to your <a href="{{ route('admin.dashboard') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">dashboard</a>.
                @elseif (auth()->user()->isStaff())
                    Head to your <a href="{{ route('staff.dashboard') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">dashboard</a>.
                @else
                    <a href="{{ route('bookings.index') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Book a court</a>.
                @endif
            </p>
        @else
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">{{ config('app.name') }}</h1>
            <p class="text-slate-600 mt-2 text-sm leading-relaxed">
                See real-time court availability and <a href="{{ route('bookings.index') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">book a court</a> —
                no account needed. Or <a href="{{ route('register') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">create an account</a> /
                <a href="{{ route('login') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">log in</a> to manage bookings more easily.
            </p>
        @endauth
    </x-card>
@endsection
