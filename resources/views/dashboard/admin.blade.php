<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard - {{ config('app.name') }}</title>
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
        @php
            $statusMeta = [
                \App\Enums\BookingStatus::Confirmed->value => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D'],
                \App\Enums\BookingStatus::Pending->value => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#B45309'],
            ];
        @endphp

        @include('partials.admin-dink-mobile-header')

        <div class="lg:flex lg:min-h-screen">
            @include('partials.admin-dink-sidebar')

            <main class="flex-1 px-5 py-6 sm:px-8 lg:px-10 lg:py-8">
                @include('partials.flash-messages')

                <div class="flex items-center justify-between mb-6">
                    <div class="text-[22px] lg:text-[32px] font-extrabold" style="color: var(--db-ink);">Dashboard</div>
                    <div class="hidden sm:block text-sm font-semibold" style="color: var(--db-ink-soft);">{{ now()->format('l, F j, Y') }}</div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-4 lg:mb-6">
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">Today's Revenue</div>
                        <div class="text-xl lg:text-[30px] font-extrabold mt-1" style="color: var(--db-ink);">₱{{ number_format($todayRevenue, 0) }}</div>
                    </div>
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">Today's Bookings</div>
                        <div class="text-xl lg:text-[30px] font-extrabold mt-1" style="color: var(--db-ink);">{{ $todayBookingsCount }}</div>
                    </div>
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">Courts Occupied</div>
                        <div class="text-xl lg:text-[30px] font-extrabold mt-1" style="color: var(--db-ink);">{{ $occupiedCourtCount }} / {{ $totalCourtCount }}</div>
                    </div>
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: #FFFBEB; border: 1px solid #FDE68A;">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: #B45309;">Pending Payments</div>
                        <div class="text-xl lg:text-[30px] font-extrabold mt-1" style="color: #B45309;">{{ $pendingPaymentsCount }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4 lg:mb-6">
                    <div class="rounded-xl lg:rounded-2xl p-5 lg:p-6 flex flex-col gap-3 lg:gap-4" style="background: #FFFBEB; border: 1px solid #FDE68A;">
                        <div class="text-base lg:text-[17px] font-bold" style="color: #92400E;">Pending Payments</div>
                        @forelse ($pendingPayments as $payment)
                            <div class="flex justify-between items-center py-2.5" style="border-bottom: 1px solid #FEF3C7;">
                                <div>
                                    <div class="text-sm font-bold" style="color: var(--db-ink);">{{ $payment->booking->user->name }}</div>
                                    <div class="text-[13px]" style="color: var(--db-ink-soft);">
                                        PB-{{ $payment->booking->id }} &middot; {{ $payment->booking->court->name }} &middot; ₱{{ number_format($payment->amount, 0) }}
                                    </div>
                                </div>
                                <a href="{{ route('bookings.show', $payment->booking) }}" class="text-xs font-bold rounded-lg px-3 py-2" style="background: #92400E; color: #FFFBEB;">Approve</a>
                            </div>
                        @empty
                            <div class="text-sm text-center py-6" style="color: #92400E;">No payments waiting on approval.</div>
                        @endforelse
                    </div>

                    <div class="rounded-xl lg:rounded-2xl p-5 lg:p-6 flex flex-col gap-3 lg:gap-4" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-base lg:text-[17px] font-bold" style="color: var(--db-ink);">Upcoming Bookings</div>
                        @forelse ($upcomingBookings as $booking)
                            @php $meta = $statusMeta[$booking->status->value] ?? ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'text' => '#64748B']; @endphp
                            <div class="flex justify-between items-center py-2.5" style="border-bottom: 1px solid #F1F5F9;">
                                <div class="flex items-center gap-3 lg:gap-4">
                                    <div class="text-sm font-bold w-16 lg:w-[70px]" style="color: var(--db-ink);">
                                        {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}
                                    </div>
                                    <div class="text-sm" style="color: var(--db-ink-soft);">{{ $booking->court->name }} &middot; {{ $booking->user->name }}</div>
                                </div>
                                <div class="text-[11px] font-bold rounded-full px-2.5 py-1" style="color: {{ $meta['text'] }}; background: {{ $meta['bg'] }}; border: 1px solid {{ $meta['border'] }};">
                                    {{ strtoupper($booking->status->label()) }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-center py-6" style="color: var(--db-ink-faint);">No more bookings today.</div>
                        @endforelse
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div class="rounded-xl lg:rounded-2xl p-5 lg:p-6 flex flex-col gap-3 lg:gap-4" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-base lg:text-[17px] font-bold" style="color: var(--db-ink);">Court Utilization</div>
                        @forelse ($utilization as $row)
                            <div class="flex flex-col gap-1.5">
                                <div class="flex justify-between text-[13px] font-semibold" style="color: var(--db-ink-soft);">
                                    <span>{{ $row['court']->name }}</span>
                                    <span style="color: var(--db-ink); font-weight: 700;">{{ $row['utilization_percent'] ?? 0 }}%</span>
                                </div>
                                <div class="h-2 rounded-full overflow-hidden" style="background: #F1F5F9;">
                                    <div class="h-full rounded-full" style="width: {{ $row['utilization_percent'] ?? 0 }}%; background: var(--db-accent);"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-center py-6" style="color: var(--db-ink-faint);">No courts are configured yet.</div>
                        @endforelse
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
