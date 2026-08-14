@extends('layouts.guest', ['title' => 'Log in'])

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" name="password" type="password" required
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
        </div>

        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
            <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
        </div>

        <x-button type="submit" class="w-full">Log in</x-button>

        <p class="text-sm text-slate-600 text-center">
            <a href="{{ route('password.request') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Forgot your password?</a>
        </p>
        <p class="text-sm text-slate-600 text-center">
            Don't have an account? <a href="{{ route('register') }}" class="text-teal-600 hover:text-teal-700 underline underline-offset-2">Register</a>
        </p>
    </form>
@endsection
