<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Check-In - {{ config('app.name') }}</title>
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
        @include('partials.admin-dink-mobile-header')

        <div class="lg:flex lg:min-h-screen">
            @include('partials.admin-dink-sidebar')

        <main class="flex-1 px-5 py-6 sm:px-10 sm:py-8 flex flex-col gap-5">
            @include('partials.flash-messages')

            <div class="text-[22px] sm:text-[32px] font-extrabold" style="color: var(--db-ink);">Today's Check-Ins</div>

            <form method="GET" action="{{ route('manage.checkin.index') }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search booking or customer"
                       class="w-full h-12 rounded-[10px] px-4 text-[15px]" style="border: 1px solid var(--db-border); background: #FFFFFF; color: var(--db-ink);">
            </form>

            @php
                $paymentMeta = [
                    \App\Enums\PaymentStatus::Paid->value => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D'],
                    \App\Enums\PaymentStatus::Pending->value => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#B45309'],
                    \App\Enums\PaymentStatus::Unpaid->value => ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'text' => '#64748B'],
                    \App\Enums\PaymentStatus::Failed->value => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C'],
                    \App\Enums\PaymentStatus::Refunded->value => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8'],
                    \App\Enums\PaymentStatus::PartiallyRefunded->value => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8'],
                ];
                $finishedStatusMeta = [
                    \App\Enums\BookingStatus::Completed->value => ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'text' => '#64748B'],
                    \App\Enums\BookingStatus::Cancelled->value => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C'],
                    \App\Enums\BookingStatus::NoShow->value => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#B91C1C'],
                    \App\Enums\BookingStatus::Expired->value => ['bg' => '#F1F5F9', 'border' => '#E2E8F0', 'text' => '#64748B'],
                ];
                $actionable = [\App\Enums\BookingStatus::Pending, \App\Enums\BookingStatus::Confirmed];
            @endphp

            @if ($bookings->isEmpty())
                <div class="rounded-2xl p-8 text-center text-sm" style="background: var(--db-surface); border: 1px solid var(--db-border); color: var(--db-ink-faint);">
                    {{ request('q') ? 'No bookings today match your search.' : 'No bookings today.' }}
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($bookings as $booking)
                        @php
                            $isActionable = in_array($booking->status, $actionable, true);
                            $payMeta = $paymentMeta[$booking->payment_status->value] ?? $paymentMeta[\App\Enums\PaymentStatus::Unpaid->value];
                        @endphp
                        <div class="rounded-2xl p-5 lg:p-6" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6">
                                    <div>
                                        <div class="text-base font-extrabold" style="color: var(--db-ink); letter-spacing: 0.02em;">PB-{{ $booking->id }}</div>
                                        <div class="text-sm mt-0.5" style="color: var(--db-ink-soft);">{{ $booking->user->name }}</div>
                                    </div>
                                    <div class="hidden sm:block w-px h-8" style="background: var(--db-border);"></div>
                                    <div>
                                        <div class="text-[15px] font-bold" style="color: var(--db-ink);">{{ $booking->court->name }}</div>
                                        <div class="text-sm" style="color: var(--db-ink-soft);">
                                            {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $booking->end_time)->format('g:i A') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2.5">
                                    <div class="text-xs font-bold rounded-full px-3 py-1.5" style="color: {{ $payMeta['text'] }}; background: {{ $payMeta['bg'] }}; border: 1px solid {{ $payMeta['border'] }};">
                                        {{ strtoupper($booking->payment_status->label()) }}
                                    </div>

                                    @if ($isActionable)
                                        <div class="text-xs font-bold rounded-full px-3 py-1.5" style="color: {{ $booking->checked_in_at ? '#15803D' : '#B45309' }}; background: {{ $booking->checked_in_at ? '#F0FDF4' : '#FFFBEB' }}; border: 1px solid {{ $booking->checked_in_at ? '#BBF7D0' : '#FDE68A' }};">
                                            {{ $booking->checked_in_at ? 'CHECKED IN' : 'AWAITING' }}
                                        </div>

                                        <form method="POST" action="{{ route('manage.checkin.bookings.complete', $booking) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold" style="color: #3B82F6;">Complete</button>
                                        </form>
                                        <form method="POST" action="{{ route('manage.checkin.bookings.no-show', $booking) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold" style="color: #B91C1C;">No-Show</button>
                                        </form>

                                        @if ($booking->checked_in_at)
                                            <div class="px-[22px] py-2.5 rounded-lg text-sm font-bold" style="background: #F1F5F9; color: var(--db-ink-faint);">Checked In &check;</div>
                                        @else
                                            <form method="POST" action="{{ route('manage.checkin.bookings.check-in', $booking) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-[22px] py-2.5 rounded-lg text-sm font-bold" style="background: var(--db-accent); color: var(--db-accent-ink);">Check In</button>
                                            </form>
                                        @endif
                                    @else
                                        @php $fMeta = $finishedStatusMeta[$booking->status->value] ?? $finishedStatusMeta[\App\Enums\BookingStatus::Completed->value]; @endphp
                                        <div class="text-xs font-bold rounded-full px-3 py-1.5" style="color: {{ $fMeta['text'] }}; background: {{ $fMeta['bg'] }}; border: 1px solid {{ $fMeta['border'] }};">
                                            {{ strtoupper($booking->status->label()) }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($booking->payment?->reference_number || $booking->payment?->payment_proof_path)
                                <div class="text-xs mt-3 flex items-center gap-3" style="color: var(--db-ink-faint);">
                                    @if ($booking->payment->reference_number)
                                        <span>Ref: <span class="font-mono">{{ $booking->payment->reference_number }}</span></span>
                                    @endif
                                    @if ($booking->payment->payment_proof_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($booking->payment->payment_proof_path) }}" target="_blank" rel="noopener" class="font-semibold" style="color: #3B82F6;">View Screenshot</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="rounded-2xl p-5 lg:p-6 flex flex-col gap-3" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                <div class="text-base font-bold" style="color: var(--db-ink);">Court Status</div>
                @foreach ($courts as $court)
                    <div class="flex items-center justify-between gap-3 py-2" style="border-top: 1px solid #F1F5F9;">
                        <div class="flex items-center gap-3">
                            <div class="text-sm font-bold" style="color: var(--db-ink);">{{ $court->name }}</div>
                            <div class="text-xs font-bold rounded-full px-2.5 py-1" style="color: {{ $court->status === \App\Enums\CourtStatus::Active ? '#15803D' : '#64748B' }}; background: {{ $court->status === \App\Enums\CourtStatus::Active ? '#F0FDF4' : '#F1F5F9' }}; border: 1px solid {{ $court->status === \App\Enums\CourtStatus::Active ? '#BBF7D0' : '#E2E8F0' }};">
                                {{ strtoupper($court->status->label()) }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('manage.checkin.courts.update-status', $court) }}">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.requestSubmit()"
                                    class="rounded-lg text-sm px-2.5 h-9" style="border: 1px solid var(--db-border); color: var(--db-ink);">
                                @foreach (\App\Enums\CourtStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($court->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @endforeach
            </div>
        </main>
        </div>
    </body>
</html>
