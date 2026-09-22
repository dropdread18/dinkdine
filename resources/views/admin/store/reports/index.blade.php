@extends('layouts.app', ['title' => 'Store Reports'])

@section('content')
    <x-page-header title="Store Reports" />

    @include('partials.store-subnav')

    <x-card class="text-center text-slate-500 text-sm py-10">
        <p class="font-medium text-slate-700 mb-1">Coming soon.</p>
        <p>Daily sales, product sales, payment breakdown, and inventory status reports will be built here in a later phase.</p>
    </x-card>
@endsection
