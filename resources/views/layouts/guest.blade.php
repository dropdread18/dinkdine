<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="flex items-center justify-center gap-2 mb-6">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600 text-white text-sm font-semibold">PB</span>
                <span class="text-lg font-semibold text-slate-900">{{ config('app.name') }}</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                @isset($title)
                    <h1 class="text-lg font-semibold text-slate-900 mb-6 text-center">{{ $title }}</h1>
                @endisset

                @include('partials.flash-messages')

                @yield('content')
            </div>
        </div>
    </body>
</html>
