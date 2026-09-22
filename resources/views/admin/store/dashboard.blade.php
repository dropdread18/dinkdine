@extends('layouts.app', ['title' => 'Store'])

@section('content')
    <x-page-header title="Store" />

    @include('partials.store-subnav')

    <x-card class="text-center text-slate-500 text-sm py-10">
        <p class="font-medium text-slate-700 mb-1">Store module foundation is in place.</p>
        <p>Products, Inventory, POS, Sales, and Reports will be built out in later phases.</p>
    </x-card>
@endsection
