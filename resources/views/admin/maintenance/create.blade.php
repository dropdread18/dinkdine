@extends('layouts.app', ['title' => 'Schedule Maintenance'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">Schedule Maintenance</h1>

    <form method="POST" action="{{ route('admin.maintenance.store') }}" class="max-w-sm space-y-4">
        @csrf
        @include('admin.maintenance._form')

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Schedule Maintenance
        </button>

        <a href="{{ route('admin.maintenance.index') }}" class="block text-center text-sm text-gray-600 underline">Cancel</a>
    </form>
@endsection
