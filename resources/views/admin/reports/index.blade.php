<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.favicon')
        <title>Reports - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --db-nav: #163732;
                --db-page-bg: #F7F3E7;
                --db-surface: #FFFFFF;
                --db-border: #E2E8F0;
                --db-ink: #0F172A;
                --db-ink-soft: #475569;
                --db-ink-faint: #64748B;
                --db-ink-faintest: #94A3B8;
                --db-accent: #A31E22;
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

            <main class="flex-1 px-5 py-6 sm:px-8 lg:px-10 lg:py-8">
                @include('partials.flash-messages')

                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <div class="text-[22px] lg:text-[32px] font-extrabold" style="color: var(--db-ink);">Reports</div>

                    <div class="flex flex-wrap gap-2">
                        @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $value => $label)
                            <a href="{{ route('manage.reports.index', ['range' => $value]) }}"
                               class="px-4 py-2 rounded-lg text-[13px] font-bold"
                               style="background: {{ $range === $value ? 'var(--db-nav)' : 'var(--db-surface)' }}; color: {{ $range === $value ? '#FFFFFF' : 'var(--db-ink-soft)' }}; border: 1px solid {{ $range === $value ? 'var(--db-nav)' : 'var(--db-border)' }};">
                                {{ $label }}
                            </a>
                        @endforeach

                        <form method="GET" action="{{ route('manage.reports.index') }}" class="flex items-center gap-2">
                            <input type="hidden" name="range" value="custom">
                            <input type="date" name="start" value="{{ $start->toDateString() }}"
                                   class="rounded-lg text-[13px] px-2.5 h-[38px]" style="border: 1px solid var(--db-border); color: var(--db-ink);">
                            <span class="text-[13px]" style="color: var(--db-ink-faint);">to</span>
                            <input type="date" name="end" value="{{ $end->toDateString() }}"
                                   class="rounded-lg text-[13px] px-2.5 h-[38px]" style="border: 1px solid var(--db-border); color: var(--db-ink);">
                            <button type="submit" class="px-4 py-2 rounded-lg text-[13px] font-bold"
                                    style="background: {{ $range === 'custom' ? 'var(--db-nav)' : 'var(--db-surface)' }}; color: {{ $range === 'custom' ? '#FFFFFF' : 'var(--db-ink-soft)' }}; border: 1px solid {{ $range === 'custom' ? 'var(--db-nav)' : 'var(--db-border)' }};">
                                Custom
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-sm mb-5" style="color: var(--db-ink-faint);">
                    Showing {{ $start->format('M j, Y') }}
                    @if (! $start->isSameDay($end))
                        – {{ $end->format('M j, Y') }}
                    @endif
                </p>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-4 lg:mb-6">
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">Total Revenue</div>
                        <div class="text-xl lg:text-[28px] font-extrabold mt-1" style="color: var(--db-ink);">₱{{ number_format($revenue['total'], 2) }}</div>
                    </div>
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">Confirmed</div>
                        <div class="text-xl lg:text-[28px] font-extrabold mt-1" style="color: var(--db-ink);">{{ $bookingCounts['confirmed'] }}</div>
                    </div>
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">Cancelled</div>
                        <div class="text-xl lg:text-[28px] font-extrabold mt-1" style="color: var(--db-ink);">{{ $bookingCounts['cancelled'] }}</div>
                    </div>
                    <div class="rounded-xl lg:rounded-2xl p-4 lg:p-5" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                        <div class="text-xs lg:text-[13px] font-semibold" style="color: var(--db-ink-faint);">No-Shows</div>
                        <div class="text-xl lg:text-[28px] font-extrabold mt-1" style="color: var(--db-ink);">{{ $bookingCounts['no_show'] }}</div>
                    </div>
                </div>

                <div class="rounded-xl lg:rounded-2xl p-5 lg:p-6 flex flex-col gap-4 mb-4 lg:mb-6" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                    <div class="text-base lg:text-[17px] font-bold" style="color: var(--db-ink);">Revenue — Last 7 Days</div>
                    @php $max7 = $last7Days->max('total') ?: 1; @endphp
                    <div class="flex items-end gap-2 lg:gap-4" style="height: 160px;">
                        @foreach ($last7Days as $day)
                            <div class="flex-1 flex flex-col items-center justify-end gap-2" style="height: 100%;">
                                <div class="text-[11px] lg:text-xs font-bold" style="color: var(--db-ink);">₱{{ number_format($day['total'], 0) }}</div>
                                <div class="w-full rounded-t-md" style="height: {{ max(round(($day['total'] / $max7) * 100), $day['total'] > 0 ? 2 : 0) }}px; background: var(--db-accent);"></div>
                                <div class="text-[11px] lg:text-xs font-semibold" style="color: var(--db-ink-faint);">{{ $day['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl lg:rounded-2xl p-5 lg:p-6 flex flex-col gap-4 mb-4 lg:mb-6" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                    <div class="text-base lg:text-[17px] font-bold" style="color: var(--db-ink);">Court Utilization</div>
                    @forelse ($utilization as $row)
                        <div class="flex items-center gap-4">
                            <div class="w-16 lg:w-[70px] text-sm font-bold shrink-0" style="color: var(--db-ink);">{{ $row['court']->name }}</div>
                            <div class="flex-1 h-2.5 rounded-full overflow-hidden" style="background: #F1F5F9;">
                                <div class="h-full rounded-full" style="width: {{ $row['utilization_percent'] ?? 0 }}%; background: var(--db-accent);"></div>
                            </div>
                            <div class="w-10 text-sm font-bold text-right shrink-0" style="color: var(--db-ink);">{{ $row['utilization_percent'] ?? 0 }}%</div>
                        </div>
                    @empty
                        <div class="text-sm text-center py-6" style="color: var(--db-ink-faint);">No courts are configured yet.</div>
                    @endforelse
                </div>

                <div class="rounded-xl lg:rounded-2xl p-5 flex flex-wrap gap-4 items-center" style="background: var(--db-surface); border: 1px solid var(--db-border);">
                    <div class="text-sm font-bold" style="color: var(--db-ink);">Export</div>
                    <a href="{{ route('manage.reports.export-bookings', request()->query()) }}" class="text-sm font-semibold" style="color: #3B82F6;">Export Bookings (CSV)</a>
                    <a href="{{ route('manage.reports.export-payments', request()->query()) }}" class="text-sm font-semibold" style="color: #3B82F6;">Export Payments (CSV)</a>
                </div>
            </main>
        </div>
    </body>
</html>
