@extends('layouts.app', ['title' => 'Schedule Open Play'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">Schedule Open Play</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.open-play.store') }}" class="space-y-4">
            @csrf
            @include('admin.open-play._form')

            <x-button type="submit" class="w-full">Schedule Open Play</x-button>

            <a href="{{ route('admin.open-play.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
