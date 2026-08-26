@extends('layouts.app', ['title' => 'New Account'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">New Staff or Admin Account</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                <select id="role" name="role" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    <option value="organizer" @selected(old('role') === 'organizer')>Organizer</option>
                </select>
                <p class="text-xs text-slate-500 mt-1">Admins have full access, including settings and account management. Organizer is a restricted account for an Open Play organizer - it can only see the booking schedule and manage Open Play sessions, nothing else (no payments, reports, or settings).</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <p class="text-sm text-slate-500">
                Share this password with the staff member directly — there's no invite email yet.
            </p>

            <x-button type="submit" class="w-full">Create Account</x-button>

            <a href="{{ route('admin.staff.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
