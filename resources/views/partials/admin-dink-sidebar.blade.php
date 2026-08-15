@php
    $navItems = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
        ['label' => 'Courts', 'route' => 'admin.courts.index', 'pattern' => 'admin.courts.*'],
        ['label' => 'Maintenance', 'route' => 'admin.maintenance.index', 'pattern' => 'admin.maintenance.*'],
        ['label' => 'Court Schedule', 'route' => 'manage.courts.schedule', 'pattern' => 'manage.courts.schedule'],
        ['label' => 'Bookings', 'route' => 'manage.bookings.index', 'pattern' => 'manage.bookings.*'],
        ['label' => 'Payments', 'route' => 'manage.payments.index', 'pattern' => 'manage.payments.*'],
        ['label' => 'Reports', 'route' => 'manage.reports.index', 'pattern' => 'manage.reports.*'],
        ['label' => 'Settings', 'route' => 'manage.settings.index', 'pattern' => 'manage.settings.*'],
        ['label' => 'Customers', 'route' => 'admin.customers.index', 'pattern' => 'admin.customers.*'],
        ['label' => 'Staff', 'route' => 'admin.staff.index', 'pattern' => 'admin.staff.*'],
    ];
@endphp
<aside class="hidden lg:flex lg:flex-col lg:shrink-0 lg:justify-between bg-slate-900" style="width: 240px; padding: 24px 0;">
    <div>
        <div class="flex items-center gap-2 px-6 mb-6">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-accent"></span>
            <span class="text-lg font-extrabold text-white">Dink Dine</span>
        </div>
        @foreach ($navItems as $item)
            @php $isActive = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               class="block px-6 py-3 text-sm {{ $isActive ? 'font-bold text-white bg-accent/10 border-l-[3px] border-accent' : 'font-medium text-slate-400 border-l-[3px] border-transparent hover:text-white' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    <div class="px-6 pt-4 border-t border-white/10">
        <div class="text-xs font-medium text-slate-400 mb-3">{{ auth()->user()->name }} &middot; {{ auth()->user()->role->label() }}</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-400 hover:text-white">Logout</button>
        </form>
    </div>
</aside>
