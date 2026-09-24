@extends('layouts.app', ['title' => 'Store POS'])

@section('content')
    @vite(['resources/js/barcode-scanner.js'])

    <x-page-header title="Store POS">
        <x-slot:actions>
            <x-barcode-scanner-modal id="pos-scanner" label="Scan Barcode" variant="button" />
        </x-slot:actions>
    </x-page-header>

    @include('partials.store-subnav')

    @if ($products->isEmpty())
        <x-card class="text-center text-slate-500 text-sm py-8">No active products yet.</x-card>
    @else
        <form method="GET" action="{{ route('admin.store.pos.review') }}"
              x-data="{
                  count: 0,
                  search: '',
                  matches(card) {
                      const q = this.search.trim().toLowerCase();
                      return q === '' || card.dataset.search.includes(q);
                  },
                  scanMessage: '',
                  scanIsError: false,
                  refreshCount() {
                      this.count = Array.from(document.querySelectorAll('.pos-qty')).filter(el => parseInt(el.value) > 0).length;
                  },
                  flashScanMessage(message, isError) {
                      this.scanMessage = message;
                      this.scanIsError = isError;
                      setTimeout(() => { if (this.scanMessage === message) this.scanMessage = ''; }, 2500);
                  },
                  handleScan(barcode) {
                      const card = document.querySelector('[data-barcode=\'' + CSS.escape(barcode) + '\']');
                      if (! card) {
                          this.flashScanMessage('No product found for barcode ' + barcode, true);
                          return;
                      }
                      const input = card.querySelector('.pos-qty');
                      if (input.disabled) {
                          this.flashScanMessage(card.dataset.name + ' is out of stock', true);
                          return;
                      }
                      const max = parseInt(input.max) || Infinity;
                      const next = (parseInt(input.value) || 0) + 1;
                      if (next > max) {
                          this.flashScanMessage('Only ' + max + ' ' + card.dataset.name + ' left in stock', true);
                          return;
                      }
                      input.value = next;
                      input.dispatchEvent(new Event('input', { bubbles: true }));
                      card.classList.add('ring-2', 'ring-green-500');
                      setTimeout(() => card.classList.remove('ring-2', 'ring-green-500'), 600);
                      this.flashScanMessage('Added ' + card.dataset.name, false);
                  },
              }"
              @input="refreshCount()"
              @barcode-scanned.window="handleScan($event.detail.text)"
              class="pb-24">
            {{-- Filtered in the browser, not by reloading the page: every active
                 product is already on the grid, so hiding non-matches keeps the
                 quantities already entered for other products intact (hidden
                 rows still submit with the form). --}}
            <div class="flex flex-wrap gap-2 mb-6 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-3">
                <input type="text" x-model="search" @keydown.enter.prevent placeholder="Search name, SKU, or barcode" autocomplete="off"
                       class="rounded-lg border-slate-300 shadow-sm w-64 focus:border-blue-500 focus:ring-blue-500">
                <button type="button" x-show="search" x-cloak @click="search = ''" class="self-center text-blue-600 underline underline-offset-2">Clear</button>
            </div>

            <div x-show="scanMessage" x-cloak x-text="scanMessage"
                 class="mb-4 text-sm font-medium rounded-lg px-3 py-2"
                 :class="scanIsError ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($products as $product)
                    {{-- data-barcode is empty (never a real scanned code) for a product with no
                         barcode set - harmless, handleScan() only ever looks up a non-empty value. --}}
                    <x-card data-barcode="{{ $product->barcode }}" data-name="{{ $product->name }}" data-search="{{ strtolower($product->name.' '.$product->sku.' '.$product->barcode) }}" x-show="matches($el)" class="flex items-center justify-between gap-3">
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
