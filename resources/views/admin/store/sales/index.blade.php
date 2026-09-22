@extends('layouts.app', ['title' => 'Store Sales'])

@section('content')
    <x-page-header title="Sales" />

    @include('partials.store-subnav')

    <x-card class="text-center text-slate-500 text-sm py-10">
        <p class="font-medium text-slate-700 mb-1">Coming soon.</p>
        <p>Sales history, receipts, and date/payment filtering will be built here in a later phase.</p>
    </x-card>
@endsection
