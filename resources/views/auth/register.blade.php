@extends('layouts.guest', ['title' => 'Register'])

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Register
        </button>

        <p class="text-sm text-gray-600 text-center">
            Already have an account? <a href="{{ route('login') }}" class="underline">Log in</a>
        </p>
    </form>
@endsection
