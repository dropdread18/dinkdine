@php
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['label' => 'Courts', 'route' => 'admin.courts.index'],
        ['label' => 'Maintenance', 'route' => 'admin.maintenance.index'],
        ['label' => 'Court Schedule', 'route' => 'manage.courts.schedule'],
        ['label' => 'Bookings', 'route' => 'manage.bookings.index'],
        ['label' => 'Payments', 'route' => 'manage.payments.index'],
        ['label' => 'Reports', 'route' => 'manage.reports.index'],
        ['label' => 'Settings', 'route' => 'manage.settings.index'],
        ['label' => 'Customers', 'route' => 'admin.customers.index'],
        ['label' => 'Staff', 'route' => 'admin.staff.index'],
    ];
@endphp
<header class="lg:hidden bg-slate-900">
    <div class="px-5 h-16 flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-accent"></span>
            <span class="text-base font-extrabold text-white">Dink Dine</span>
        </a>

        <details class="relative">
            <summary class="list-none cursor-pointer text-sm font-semibold text-white px-3 py-1.5 rounded-lg border border-white/20">Menu</summary>
            <div class="absolute right-0 mt-2 w-56 bg-slate-900 rounded-lg shadow-lg py-2 z-20">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" class="block px-4 py-2 text-sm {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) || request()->routeIs($item['route']) ? 'text-white font-semibold' : 'text-slate-400' }} hover:text-white hover:bg-white/5">
                        {{ $item['label'] }}
                    </a>
                @endforeach
                <div class="border-t border-white/10 my-2"></div>
                <div class="px-4 py-1 text-xs text-slate-500">{{ auth()->user()->name }} &middot; {{ auth()->user()->role->label() }}</div>
                <form method="POST" action="{{ route('logout') }}" class="px-4 pt-1">
                    @csrf
                    <button type="submit" class="text-sm text-slate-400 hover:text-white py-1">Logout</button>
                </form>
            </div>
        </details>
    </div>
</header>
