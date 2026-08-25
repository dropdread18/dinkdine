<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.favicon')
        <title>Book a Court - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --db-nav: #0F172A;
                --db-page-bg: #EEF1EA;
                --db-surface: #FFFFFF;
                --db-border: #E2E8F0;
                --db-ink: #0F172A;
                --db-ink-soft: #475569;
                --db-ink-faint: #64748B;
                --db-ink-faintest: #94A3B8;
                --db-accent: #B8E63E;
                --db-accent-hover: #7A9F20;
                --db-accent-ink: #0F172A;
                --db-link: #3B82F6;
                --db-font: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            }
            body.db-body {
                margin: 0;
                background: var(--db-page-bg);
                color: var(--db-ink);
                font-family: var(--db-font);
                -webkit-font-smoothing: antialiased;
            }
            .db-body a { color: var(--db-link); }
            .db-body a:hover { color: var(--db-ink); }
        </style>
    </head>
    <body class="db-body">
        @include('partials.nav')

        <main class="mx-auto max-w-[1440px] px-5 py-6 sm:px-10 sm:py-8">
            @include('partials.flash-messages')

            @php
                $prevDate = \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString();
                $nextDate = \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString();
            @endphp

            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between mb-6">
                <div>
                    <h1 class="text-[28px] sm:text-[36px] font-extrabold leading-tight" style="color: var(--db-ink);">Book a Court</h1>
                    <p class="text-sm sm:text-base mt-1" style="color: var(--db-ink-soft);">Select an available time slot to get started.</p>
                </div>

                <div class="flex items-center gap-3 sm:gap-4 rounded-xl px-3 py-2 sm:px-4 self-start" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                    @if ($prevDate >= $minDate)
                        <a href="{{ route('bookings.index', ['date' => $prevDate]) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-lg" style="color: var(--db-ink);">&lsaquo;</a>
                    @else
                        <span class="w-8 h-8 flex items-center justify-center text-lg" style="color: var(--db-border);">&lsaquo;</span>
                    @endif

                    <input type="date" value="{{ $date }}" min="{{ $minDate }}" max="{{ $maxDate }}"
                           aria-label="Jump to date"
                           onchange="if (this.value) window.location.href = '{{ route('bookings.index', ['date' => '__DATE__']) }}'.replace('__DATE__', this.value)"
                           class="text-sm sm:text-base font-bold bg-transparent border-0 focus:outline-none cursor-pointer min-w-[150px] sm:min-w-[170px]"
                           style="color: var(--db-ink);">

                    @if ($nextDate <= $maxDate)
                        <a href="{{ route('bookings.index', ['date' => $nextDate]) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-lg" style="color: var(--db-ink);">&rsaquo;</a>
                    @else
                        <span class="w-8 h-8 flex items-center justify-center text-lg" style="color: var(--db-border);">&rsaquo;</span>
                    @endif
                </div>
            </div>

            <livewire:booking-grid :date="$date" :key="$date" />
        </main>
    </body>
</html>
