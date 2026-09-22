@extends('layouts.app', ['title' => 'Store POS'])

@section('content')
    <x-page-header title="Store POS" />

    @include('partials.store-subnav')

    <form method="GET" action="{{ route('admin.store.pos.index') }}" class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name or SKU"
               class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-blue-500 focus:ring-blue-500">
        <x-button type="submit">Search</x-button>
        <x-button tag="a" href="{{ route('admin.store.pos.index') }}" variant="ghost" class="self-center">Clear</x-button>
    </form>

    @if ($products->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No active products match this search.</x-card>
    @else
        <form method="GET" action="{{ route('admin.store.pos.review') }}"
              x-data="{
                  count: 0,
                  refreshCount() {
                      this.count = Array.from(document.querySelectorAll('.pos-qty')).filter(el => parseInt(el.value) > 0).length;
                  },
              }"
              @input="refreshCount()"
              class="pb-24">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($products as $product)
                    <x-card class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-900 truncate">{{ $product->name }}</div>
                            <div class="text-xs text-slate-500">{{ $product->category?->name ?: '—' }} &middot; ₱{{ number_format($product->selling_price, 2) }} / {{ $product->unit }}</div>
                            <div class="text-xs {{ $product->isOutOfStock() ? 'text-red-600' : 'text-slate-500' }}">
                                {{ $product->isOutOfStock() ? 'Out of stock' : $product->stock_quantity.' '.$product->unit.' left' }}
                            </div>
                        </div>
                        <input type="number" name="quantities[{{ $product->id }}]" min="0" max="{{ $product->stock_quantity }}"
                               value="{{ request('quantities.'.$product->id) }}" placeholder="0" @disabled($product->isOutOfStock())
                               class="pos-qty w-16 rounded-lg border-slate-300 shadow-sm text-sm text-center focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100">
                    </x-card>
                @endforeach
            </div>

            <div x-show="count > 0" x-cloak
                 class="fixed bottom-0 left-0 right-0 lg:left-[240px] px-5 py-4 flex flex-wrap items-center gap-3"
                 style="background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -8px 24px rgba(15,23,42,0.08);">
                <span class="text-sm font-semibold text-slate-700" x-text="count + ' product' + (count === 1 ? '' : 's') + ' selected'"></span>
                <x-button type="submit">Review Order</x-button>
            </div>
        </form>
    @endif
@endsection
