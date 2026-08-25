<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.favicon')
        <title>Booking Confirmed - {{ config('app.name') }}</title>
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
                --db-accent: #B8E63E;
                --db-accent-ink: #0F172A;
                --db-font: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            }
            body.db-body {
                margin: 0;
                background: var(--db-page-bg);
                color: var(--db-ink);
                font-family: var(--db-font);
                -webkit-font-smoothing: antialiased;
            }
        </style>
    </head>
    <body class="db-body">
        <header style="background: var(--db-nav);">
            <div class="mx-auto max-w-[1440px] px-5 sm:px-10 h-16 sm:h-[72px] flex items-center">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    @if ($brandLogoUrl)
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-7 sm:h-8 w-auto max-w-[160px] object-contain">
                    @else
                        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--db-accent);"></span>
                        <span class="text-lg sm:text-xl font-extrabold text-white tracking-tight" style="font-family: var(--db-font);">{{ $brandName }}</span>
                    @endif
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-[560px] px-5 py-12 sm:py-16 flex flex-col items-center text-center gap-6">
            @php
                $statusMeta = [
                    \App\Enums\PaymentStatus::Paid->value => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D'],
                    \App\Enums\PaymentStatus::Pending->value => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#B45309'],
                    \App\Enums\PaymentStatus::Unpaid->value => ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'text' => '#64748B'],
                    \App\Enums\PaymentStatus::Failed->value => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C'],
                    \App\Enums\PaymentStatus::Refunded->value => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8'],
                    \App\Enums\PaymentStatus::PartiallyRefunded->value => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8'],
                ];
            @endphp

            <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-extrabold" style="background: #F0FDF4; border: 2px solid #22C55E; color: #22C55E;">&check;</div>

            <div>
                <div class="text-[28px] sm:text-[32px] font-extrabold" style="color: var(--db-ink);">
                    {{ $bookings->count() === 1 ? 'Booking Confirmed' : $bookings->count().' Bookings Confirmed' }}
                </div>
                <div class="text-base mt-1" style="color: var(--db-ink-soft);">A confirmation has been sent to your email.</div>
            </div>

            <div class="w-full flex flex-col gap-4">
                @foreach ($bookings as $booking)
                    @php $meta = $statusMeta[$booking->payment_status->value] ?? $statusMeta[\App\Enums\PaymentStatus::Unpaid->value]; @endphp
                    <div class="rounded-2xl p-6 sm:p-7 flex flex-col gap-4 text-left" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="flex justify-between items-center">
                            <div class="text-lg sm:text-xl font-extrabold" style="color: var(--db-ink); letter-spacing: 0.02em;">PB-{{ $booking->id }}</div>
                            <div class="text-xs font-bold rounded-full px-3 py-1.5" style="color: {{ $meta['text'] }}; background: {{ $meta['bg'] }}; border: 1px solid {{ $meta['border'] }};">{{ strtoupper($booking->payment_status->label()) }}</div>
                        </div>
                        <div class="h-px" style="background: var(--db-border);"></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase" style="color: var(--db-ink-faint); letter-spacing: 0.04em;">Court</div>
                                <div class="text-base font-bold mt-1" style="color: var(--db-ink);">{{ $booking->court->name }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase" style="color: var(--db-ink-faint); letter-spacing: 0.04em;">Date</div>
                                <div class="text-base font-bold mt-1" style="color: var(--db-ink);">{{ $booking->booking_date->format('M j, Y') }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase" style="color: var(--db-ink-faint); letter-spacing: 0.04em;">Time</div>
                                <div class="text-base font-bold mt-1" style="color: var(--db-ink);">
                                    {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase" style="color: var(--db-ink-faint); letter-spacing: 0.04em;">Amount</div>
                                <div class="text-base font-bold mt-1" style="color: var(--db-ink);">₱{{ number_format($booking->price, 0) }}</div>
                            </div>
                        </div>
                        @if ($bookings->count() > 1)
                            <a href="{{ route('bookings.receipt', $booking) }}" class="text-sm font-semibold" style="color: #3B82F6;">Print Receipt</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="w-full flex flex-col sm:flex-row gap-3">
                @if ($bookings->count() === 1)
                    <a href="{{ route('bookings.show', $bookings->first()) }}" class="flex-1 text-center font-bold text-[15px] py-3.5 rounded-lg" style="background: var(--db-accent); color: var(--db-accent-ink);">View My Booking</a>
                    <a href="{{ route('bookings.receipt', $bookings->first()) }}" class="flex-1 text-center font-bold text-[15px] py-3.5 rounded-lg" style="background: var(--db-surface); border: 1px solid var(--db-border); color: var(--db-ink);">Print Receipt</a>
                @else
                    <a href="{{ route('bookings.mine') }}" class="flex-1 text-center font-bold text-[15px] py-3.5 rounded-lg" style="background: var(--db-accent); color: var(--db-accent-ink);">View My Bookings</a>
                @endif
            </div>
        </main>
    </body>
</html>
