<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.favicon')
        <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- Livewire ships its own bundled Alpine.js and only auto-injects
             it on pages that actually mount a <livewire:...> component -
             that's only bookings/index.blade.php in this app. Every other
             page using x-data (Open Play modals on staff schedule/walk-in/
             reschedule, this admin layout's own pages) had no Alpine
             runtime at all until these two directives were added here. --}}
        @livewireStyles
    </head>
    <body class="min-h-screen bg-mint text-slate-900">
        @if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isStaff() || auth()->user()->isOrganizer()))
            @include('partials.admin-dink-mobile-header')

            <div class="lg:flex lg:min-h-screen">
                @include('partials.admin-dink-sidebar')

                <main class="flex-1 px-5 py-6 sm:px-8 lg:px-10 lg:py-8">
                    @include('partials.flash-messages')

                    @yield('content')
                </main>
            </div>
        @else
            @include('partials.nav')

            <main class="max-w-5xl mx-auto px-4 py-10 sm:px-6">
                @include('partials.flash-messages')

                @yield('content')
            </main>
        @endif

        @livewireScripts
    </body>
</html>
