@extends('layouts.app', ['title' => 'New Court'])

@section('content')
    <h1 class="text-xl font-semibold text-gray-900 mb-4">New Court</h1>

    <form method="POST" action="{{ route('admin.courts.store') }}" enctype="multipart/form-data" class="max-w-sm space-y-4">
        @csrf
        @include('admin.courts._form')

        <button type="submit" class="w-full bg-gray-900 text-white rounded py-2 text-sm font-medium">
            Create Court
        </button>

        <a href="{{ route('admin.courts.index') }}" class="block text-center text-sm text-gray-600 underline">Cancel</a>
    </form>
@endsection
