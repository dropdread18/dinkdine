@extends('layouts.guest', ['title' => 'Forgot Password'])

@section('content')
    <p class="text-sm text-slate-600 mb-4">
        Enter your email and we'll send you a password reset link.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>

        <x-button type="submit" class="w-full">Email Password Reset Link</x-button>
    </form>
@endsection
