@extends('layouts.app', ['title' => 'Store Reports'])

@section('content')
    <x-page-header title="Store Reports" />

    @include('partials.store-subnav')

    <div class="flex flex-wrap items-center gap-2 mb-4 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        @foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $value => $label)
            <a href="{{ route('admin.store.reports.index', ['range' => $value]) }}"
               class="px-3 py-1.5 rounded-lg font-medium {{ $range === $value ? 'bg-accent text-white' : 'text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.store.reports.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="range" value="custom">
            <input type="date" name="start" value="{{ $start->toDateString() }}" class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            <span class="text-slate-500">to</span>
            <input type="date" name="end" value="{{ $end->toDateString() }}" class="rounded-lg border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            <button type="submit" class="px-3 py-1.5 rounded-lg font-medium {{ $range === 'custom' ? 'bg-accent text-white' : 'text-slate-600 hover:bg-slate-50 border border-slate-200' }}">
                Custom
            </button>
        </form>
    </div>

    <p class="text-sm text-slate-500 mb-5">
        Showing {{ $start->format('M j, Y') }}
        @if (! $start->isSameDay($end))
            – {{ $end->format('M j, Y') }}
        @endif
    </p>

    <div class="grid grid-cols-2 gap-3 mb-6">
        <x-card>
            <div class="text-xs font-semibold text-slate-500">Total Sales</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">₱{{ number_format($dailySales['total'], 2) }}</div>
        </x-card>
        <x-card>
            <div class="text-xs font-semibold text-slate-500">Number of Sales</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ $dailySales['count'] }}</div>
        </x-card>
    </div>

    <x-card class="mb-6">
        <div class="text-base font-bold text-slate-900 mb-4">Sales by Date</div>
        @php $maxDay = $salesByDate->max('total') ?: 1; @endphp
        <div class="flex items-end gap-2" style="height: 160px;">
            @foreach ($salesByDate as $day)
                <div class="flex-1 flex flex-col items-center justify-end gap-2 h-full">
                    <div class="text-[11px] font-bold text-slate-900">₱{{ number_format($day['total'], 0) }}</div>
                    <div class="w-full rounded-t-md bg-accent" style="height: {{ max(round(($day['total'] / $maxDay) * 100), $day['total'] > 0 ? 2 : 0) }}px;"></div>
                    <div class="text-[11px] font-semibold text-slate-500">{{ $day['label'] }}</div>
                </div>
            @endforeach
        </div>
    </x-card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <x-card>
            <div class="text-base font-bold text-slate-900 mb-3">Payment Breakdown</div>
            @if ($paymentBreakdown->sum('count') === 0)
                <p class="text-sm text-slate-500 text-center py-6">No sales in this range.</p>
            @else
                <div class="space-y-2 text-sm">
                    @foreach ($paymentBreakdown as $row)
                        @if ($row['count'] > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-600">{{ $row['method']->label() }} <span class="text-slate-400">&times;{{ $row['count'] }}</span></span>
                                <span class="font-semibold text-slate-900">₱{{ number_format($row['total'], 2) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card>
            <div class="text-base font-bold text-slate-900 mb-3">Inventory Status</div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-600">Active Products</span><span class="font-semibold text-slate-900">{{ $inventoryStatus['total'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">OK</span><x-badge color="green">{{ $inventoryStatus['ok'] }}</x-badge></div>
                <div class="flex justify-between"><span class="text-slate-600">Low Stock</span><x-badge color="amber">{{ $inventoryStatus['low'] }}</x-badge></div>
                <div class="flex justify-between"><span class="text-slate-600">Out of Stock</span><x-badge color="red">{{ $inventoryStatus['out'] }}</x-badge></div>
            </div>
        </x-card>
    </div>

    <x-card>
        <div class="text-base font-bold text-slate-900 mb-3">Product Sales</div>
        @if ($productSales->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">No sales in this range.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left font-medium text-slate-500 py-2 pr-4">Product</th>
                            <th class="text-left font-medium text-slate-500 py-2 pr-4">Qty Sold</th>
                            <th class="text-left font-medium text-slate-500 py-2 pr-4">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productSales as $row)
                            <tr class="border-b border-slate-50">
                                <td class="py-2 pr-4 text-slate-900 font-medium">{{ $row->product_name }}</td>
                                <td class="py-2 pr-4 text-slate-600">{{ $row->quantity_sold }}</td>
                                <td class="py-2 pr-4 text-slate-900 font-semibold">₱{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection
