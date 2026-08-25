@extends('layouts.app', ['title' => 'Edit Open Play Session'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Edit Open Play Session</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.open-play.update', $session) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('admin.open-play._form')

            <x-button type="submit" class="w-full">Save Changes</x-button>

            <a href="{{ route('admin.open-play.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
