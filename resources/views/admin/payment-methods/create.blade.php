@extends('layouts.app', ['title' => 'New Payment Method'])

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight mb-4">New Payment Method</h1>

    <x-card class="max-w-sm">
        <form method="POST" action="{{ route('admin.payment-methods.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @include('admin.payment-methods._form')

            <x-button type="submit" class="w-full">Add Payment Method</x-button>

            <a href="{{ route('admin.payment-methods.index') }}" class="block text-center text-sm text-slate-600 hover:text-slate-900 underline underline-offset-2">Cancel</a>
        </form>
    </x-card>
@endsection
