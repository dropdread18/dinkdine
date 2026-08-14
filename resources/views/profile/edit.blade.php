@extends('layouts.app', ['title' => 'Profile'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-6">Profile</h1>

    <section class="mb-10">
        <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Your Details</h2>

        <form method="POST" action="{{ route('profile.update') }}" class="max-w-sm space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                       class="mt-1 block w-full rounded border-gray-300 shadow-sm">
            </div>

            <button type="submit" class="bg-gray-900 text-white rounded py-2 px-4 text-sm font-medium">
                Save Changes
            </button>
        </form>
    </section>

    <section class="max-w-sm">
        <h2 class="text-sm font-medium text-gray-500 uppercase mb-3">Change Password</h2>

        <form method="POST" action="{{ route('profile.update-password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                <input id="current_password" name="current_password" type="password" required
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

            <button type="submit" class="bg-gray-900 text-white rounded py-2 px-4 text-sm font-medium">
                Update Password
            </button>
        </form>
    </section>
@endsection
