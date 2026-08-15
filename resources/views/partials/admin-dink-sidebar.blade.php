@php
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Bookings', 'route' => 'manage.bookings.index'],
        ['label' => 'Courts', 'route' => 'admin.courts.index'],
        ['label' => 'Customers', 'route' => 'admin.customers.index'],
        ['label' => 'Payments', 'route' => 'manage.payments.index'],
        ['label' => 'Reports', 'route' => 'manage.reports.index'],
    ];
@endphp
<aside class="hidden lg:flex lg:flex-col lg:shrink-0" style="width: 240px; background: var(--db-nav); padding: 24px 0;">
    <div class="flex items-center gap-2 px-6 mb-6">
        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--db-accent);"></span>
        <span class="text-lg font-extrabold text-white" style="font-family: var(--db-font);">Dink Dine</span>
    </div>
    @foreach ($navItems as $item)
        @php $isActive = $item['route'] === ($activeRoute ?? null); @endphp
        <a href="{{ route($item['route']) }}"
           class="px-6 py-3 text-sm"
           style="font-weight: {{ $isActive ? 700 : 500 }}; color: {{ $isActive ? '#FFFFFF' : 'var(--db-ink-faintest)' }}; background: {{ $isActive ? 'rgba(184,230,62,0.12)' : 'transparent' }}; border-left: 3px solid {{ $isActive ? 'var(--db-accent)' : 'transparent' }};">
            {{ $item['label'] }}
        </a>
    @endforeach
</aside>
