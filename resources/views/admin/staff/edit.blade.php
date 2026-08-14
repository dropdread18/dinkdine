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
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $staff->email) }}" required
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone (optional)</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone', $staff->phone) }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm focus:border-teal-500 focus:ring-teal-500">
            </div>

            <x-button type="submit" class="w-full">Save Changes</x-button>

            <a href="{{ route('admin.staff.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
