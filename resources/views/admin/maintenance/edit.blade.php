@extends('layouts.app', ['title' => 'Edit Maintenance Window'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">Edit Maintenance Window</h1>

    <form method="POST" action="{{ route('admin.maintenance.update', $maintenance) }}" class="max-w-sm space-y-4">
        @csrf
        @method('PUT')
        @include('admin.maintenance._form')

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Save Changes
        </button>

        <a href="{{ route('admin.maintenance.index') }}" class="block text-center text-sm text-gray-600 underline">Cancel</a>
    </form>
@endsection
