@extends('layouts.guest', ['title' => 'Reset Password'])

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Reset Password
        </button>
    </form>
@endsection
