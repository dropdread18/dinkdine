@extends('layouts.guest', ['title' => 'Forgot Password'])

@section('content')
    <p class="text-sm text-gray-600 mb-4">
        Enter your email and we'll send you a password reset link.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Email Password Reset Link
        </button>
    </form>
@endsection
