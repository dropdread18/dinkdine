@extends('layouts.app', ['title' => 'New Category'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">New Category</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.store.categories.store') }}" class="space-y-4">
            @csrf
            @include('admin.store.categories._form')

            <x-button type="submit" class="w-full">Create Category</x-button>

            <a href="{{ route('admin.store.categories.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
