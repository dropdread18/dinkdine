@extends('layouts.app', ['title' => 'New Customer'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">New Customer</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>

            <p class="text-sm text-slate-500">
                The customer will need to use "Forgot your password?" on the login page to set their own password.
            </p>

            <x-button type="submit" class="w-full">Create Customer</x-button>

            <a href="{{ route('admin.customers.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
