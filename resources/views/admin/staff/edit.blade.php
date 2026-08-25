@extends('layouts.app', ['title' => 'Edit '.$staff->name])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Edit {{ $staff->name }}</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $staff->name) }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $staff->email) }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $staff->phone) }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="pt-2 border-t border-slate-100">
                <label for="password" class="block text-sm font-medium text-slate-700">New Password (optional)</label>
                <p class="text-xs text-slate-500 mb-1">Leave blank to keep the current password. Communicate the new one to {{ $staff->name }} yourself.</p>
                <input id="password" name="password" type="password"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm New Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <x-button type="submit" class="w-full">Save Changes</x-button>

            <a href="{{ route('admin.staff.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
