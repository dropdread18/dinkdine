{{-- Local sub-navigation for the Store module's own pages. The app has no
     existing multi-level sidebar pattern to extend (every other admin
     section is a single flat page), so Store gets one top-level "Store"
     link in the main sidebar/mobile nav (pointing here) plus this
     in-page pill bar to move between its own sections - closest existing
     precedent is the pill-style date navigator on Schedule/Walk-in. --}}
{{-- Staff only ever reaches the POS page itself (handoff spec section
     35 - Products/Categories/Inventory/Sales History/Reports/Overview
     stay admin-only), so there's nothing for them to navigate to here -
     the bar just doesn't render rather than showing a row of dead-end
     links a staff cashier can't actually open. --}}
@if (auth()->user()->isAdmin())
    @php
        $storeNavItems = [
            ['label' => 'Overview', 'route' => 'admin.store.index'],
            ['label' => 'POS', 'route' => 'admin.store.pos.index'],
            ['label' => 'Products', 'route' => 'admin.store.products.index'],
            ['label' => 'Categories', 'route' => 'admin.store.categories.index'],
            ['label' => 'Inventory', 'route' => 'admin.store.inventory.index'],
            ['label' => 'Sales', 'route' => 'admin.store.sales.index'],
            ['label' => 'Reports', 'route' => 'admin.store.reports.index'],
        ];
    @endphp
    <div class="flex flex-wrap items-center gap-1 text-sm bg-white border border-slate-200 rounded-xl shadow-sm p-1.5 mb-6">
        @foreach ($storeNavItems as $item)
            <a href="{{ route($item['route']) }}"
               class="px-3 py-1.5 rounded-lg font-medium {{ request()->routeIs($item['route']) ? 'bg-accent/10 text-accent font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
@endif
