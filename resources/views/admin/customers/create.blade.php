@extends('layouts.app', ['title' => 'New Customer'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">New Customer</h1>

    <form method="POST" action="{{ route('admin.customers.store') }}" class="max-w-sm space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                   class="mt-1 block w-full rounded border-gray-300 shadow-sm">
        </div>

        <p class="text-sm text-gray-500">
            The customer will need to use "Forgot your password?" on the login page to set their own password.
        </p>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Create Customer
        </button>

        <a href="{{ route('admin.customers.index') }}" class="block text-center text-sm text-gray-600 underline">Cancel</a>
    </form>
@endsection
