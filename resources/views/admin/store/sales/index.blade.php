@extends('layouts.app', ['title' => 'Store Sales'])

@section('content')
    <x-page-header title="Sales">
        <x-slot:actions>
            <x-button tag="a" href="{{ route('admin.store.pos.index') }}">New Sale</x-button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.store-subnav')

    <form method="GET" action="{{ route('admin.store.sales.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search sale # or cashier"
               class="rounded-lg border-slate-300 shadow-sm w-56 focus:border-blue-500 focus:ring-blue-500">

        <input type="date" name="from" value="{{ $from }}" aria-label="From date"
               class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <input type="date" name="to" value="{{ $to }}" aria-label="To date"
               class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">

        <select name="payment_method" class="rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Any Payment Method</option>
            @foreach ($paymentMethods as $method)
                <option value="{{ $method->value }}" @selected(request('payment_method') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>

        <x-button type="submit">Filter</x-button>
        <x-button tag="a" href="{{ route('admin.store.sales.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($sales->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No sales match these filters.</x-card>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left font-medium text-slate-500 py-3 pl-4 pr-4">Sale #</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Date</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Cashier</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Items</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Payment</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4">Total</th>
                        <th class="text-left font-medium text-slate-500 py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                            <td class="py-3 pl-4 pr-4 text-slate-900 font-medium font-mono">{{ $sale->sale_number }}</td>
                            <td class="py-3 pr-4 text-slate-600 whitespace-nowrap">{{ $sale->created_at->format('M j, Y g:i A') }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $sale->user?->name ?: '—' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $sale->items_count }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $sale->payment_method->label() }}</td>
                            <td class="py-3 pr-4 text-slate-900 font-semibold">₱{{ number_format($sale->total, 2) }}</td>
                            <td class="py-3 pr-4"><a href="{{ route('admin.store.sales.show', $sale) }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $sales->links() }}</div>
    @endif
@endsection
