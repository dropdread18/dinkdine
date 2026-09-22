@extends('layouts.app', ['title' => 'Store Categories'])

@section('content')
    <x-page-header title="Categories">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.store.categories.create') }}">New Category</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.store-subnav')

    @if ($categories->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No categories yet.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Name</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Description</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Products</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Status</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium">{{ $category->name }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $category->description ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $category->products_count }}</td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$category->is_active ? 'green' : 'slate'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 space-x-3 whitespace-nowrap text-right">
                                <a href="{{ route('admin.store.categories.edit', $category) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">Edit</a>
                                <form method="POST" action="{{ route('admin.store.categories.toggle-active', $category) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="{{ $category->is_active ? 'text-red-600 hover:text-red-700' : 'text-blue-600 hover:text-blue-700' }} underline underline-offset-2">
                                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
