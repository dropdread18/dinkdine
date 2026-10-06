@extends('layouts.app', ['title' => 'Store'])

@section('content')
    <x-page-header title="Store" />

    @include('partials.store-subnav')

    <x-card class="text-center text-slate-500 text-sm py-10">
        <p>Choose a section above - Products, Inventory, POS, Sales or Reports - to get started.</p>
    </x-card>
@endsection
