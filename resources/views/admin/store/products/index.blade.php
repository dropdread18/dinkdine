@extends('layouts.app', ['title' => 'Store Products'])

@section('content')
    <x-page-header title="Products" />

    @include('partials.store-subnav')

    <x-card class="text-center text-slate-500 text-sm py-10">
        <p class="font-medium text-slate-700 mb-1">Coming soon.</p>
        <p>Product catalog management (add, edit, deactivate, search, filter) will be built here in a later phase.</p>
    </x-card>
@endsection
