@extends('layouts.app', ['title' => 'New Staff Account'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">New Staff Account</h1>

    <form method="POST" action="{{ route('admin.staff.store') }}" class="max-w-sm space-y-4">
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

        <p class="text-sm text-gray-500">
            Share this password with the staff member directly — there's no invite email yet.
        </p>

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Create Staff Account
        </button>

        <a href="{{ route('admin.staff.index') }}" class="block text-center text-sm text-gray-600 underline">Cancel</a>
    </form>
@endsection
