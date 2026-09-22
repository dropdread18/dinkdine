@extends('layouts.app', ['title' => 'New Product'])

@section('content')
    @vite(['resources/js/barcode-scanner.js'])

    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">New Product</h1>

    @if ($categories->isEmpty())
        <x-card class="max-w-sm text-center text-slate-500 text-sm py-8">
            <p class="mb-3">Create a category first - every product needs one.</p>
            <x-button tag="a" href="{{ route('admin.store.categories.create') }}">New Category</x-button>
        </x-card>
    @else
        <x-card class="max-w-sm">
            <form method="POST" action="{{ route('admin.store.products.store') }}" class="space-y-4">
                @csrf
                @include('admin.store.products._form')

                <x-button type="submit" class="w-full">Create Product</x-button>

                <a href="{{ route('admin.store.products.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
            </form>
        </x-card>
    @endif
@endsection
