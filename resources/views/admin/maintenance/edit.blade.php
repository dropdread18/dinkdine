@extends('layouts.app', ['title' => 'Edit Maintenance Window'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Edit Maintenance Window</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.maintenance.update', $maintenance) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.maintenance._form')

            <x-button type="submit" class="w-full">Save Changes</x-button>

            <a href="{{ route('admin.maintenance.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
