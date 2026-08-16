@php
    $isAdminUser = auth()->user()->isAdmin();
    // Same order/grouping as partials/admin-dink-sidebar.blade.php - Courts
    // now lives inside Settings, Court Schedule merged into Walk-in Booking.
    $navItems = array_filter([
        ['label' => 'Dashboard', 'route' => $isAdminUser ? 'admin.dashboard' : 'staff.dashboard', 'adminOnly' => false],
        ['label' => 'Bookings', 'route' => 'manage.bookings.index', 'adminOnly' => false],
        ['label' => 'Walk-in Booking', 'route' => 'manage.walkin.index', 'adminOnly' => false],
        ['label' => 'Check-in', 'route' => 'manage.checkin.index', 'adminOnly' => false],
        ['label' => 'Payments', 'route' => 'manage.payments.index', 'adminOnly' => false],
        ['label' => 'Maintenance', 'route' => 'admin.maintenance.index', 'adminOnly' => true],
        ['label' => 'Customers', 'route' => 'admin.customers.index', 'adminOnly' => true],
        ['label' => 'Reports', 'route' => 'manage.reports.index', 'adminOnly' => true],
        ['label' => 'Staff', 'route' => 'admin.staff.index', 'adminOnly' => true],
        ['label' => 'Settings', 'route' => 'manage.settings.index', 'adminOnly' => true],
    ], fn (array $item) => $isAdminUser || ! $item['adminOnly']);
@endphp
<header class="lg:hidden bg-slate-900">
    <div class="px-5 h-16 flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-accent"></span>
            <span class="text-base font-extrabold text-white">{{ $brandName }}</span>
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
