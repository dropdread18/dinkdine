@extends('layouts.app', ['title' => 'Profile'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-8">Profile</h1>

    <section class="mb-10">
        <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Your Details</h2>

        <x-card class="max-w-sm">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <x-button type="submit">Save Changes</x-button>
            </form>
        </x-card>
    </section>

    <section class="max-w-sm">
        <h2 class="text-sm font-medium text-slate-500 uppercase mb-3">Change Password</h2>

        @if ($user->password_set_at)
            <x-card>
                <form method="POST" action="{{ route('profile.update-password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-slate-700">Current Password</label>
                        <input id="current_password" name="current_password" type="password" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                        <input id="password" name="password" type="password" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <x-button type="submit">Update Password</x-button>
                </form>
            </x-card>
        @else
            {{-- This account was created without the person ever choosing a
                 password (guest checkout or a staff-created walk-in) - there's
                 no "current password" to confirm, so there's nothing to change
                 yet. They set a real one for the first time via the reset link. --}}
            <x-card class="text-sm text-slate-600 space-y-2">
                <p>This account doesn't have a password set yet — it was created automatically when you booked, so there's nothing to change here.</p>
                <p>
                    Want to set one so you can log in directly next time?
                    <a href="{{ route('password.request') }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Email me a reset link</a>.
                </p>
            </x-card>
        @endif
    </section>
@endsection
