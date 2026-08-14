@extends('layouts.guest', ['title' => 'Log in'])

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="rounded border-gray-300">
            <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
        </div>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Log in
        </button>

        <p class="text-sm text-gray-600 text-center">
            <a href="{{ route('password.request') }}" class="underline">Forgot your password?</a>
        </p>
        <p class="text-sm text-gray-600 text-center">
            Don't have an account? <a href="{{ route('register') }}" class="underline">Register</a>
        </p>
    </form>
@endsection
