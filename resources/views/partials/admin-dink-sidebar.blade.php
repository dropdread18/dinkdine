@php
    $isAdminUser = auth()->user()->isAdmin();
    // Order and grouping per owner request: Courts now lives inside
    // Settings (see admin/settings/index.blade.php) and Court Schedule was
    // merged into Walk-in Booking (its grid already shows full-day
    // availability for every court, not just one) - neither has its own
    // top-level item any more.
    $navItems = array_filter([
        ['label' => 'Dashboard', 'route' => $isAdminUser ? 'admin.dashboard' : 'staff.dashboard', 'pattern' => $isAdminUser ? 'admin.dashboard' : 'staff.dashboard', 'adminOnly' => false],
        ['label' => 'Bookings', 'route' => 'manage.bookings.index', 'pattern' => 'manage.bookings.*', 'adminOnly' => false],
        ['label' => 'Walk-in Booking', 'route' => 'manage.walkin.index', 'pattern' => 'manage.walkin.*', 'adminOnly' => false],
        ['label' => 'Check-in', 'route' => 'manage.checkin.index', 'pattern' => 'manage.checkin.*', 'adminOnly' => false],
        ['label' => 'Payments', 'route' => 'manage.payments.index', 'pattern' => 'manage.payments.*', 'adminOnly' => false],
        ['label' => 'Maintenance', 'route' => 'admin.maintenance.index', 'pattern' => 'admin.maintenance.*', 'adminOnly' => true],
        ['label' => 'Open Play', 'route' => 'admin.open-play.index', 'pattern' => 'admin.open-play.*', 'adminOnly' => true],
        ['label' => 'Customers', 'route' => 'admin.customers.index', 'pattern' => 'admin.customers.*', 'adminOnly' => true],
        ['label' => 'Reports', 'route' => 'manage.reports.index', 'pattern' => 'manage.reports.*', 'adminOnly' => true],
        ['label' => 'Staff', 'route' => 'admin.staff.index', 'pattern' => 'admin.staff.*', 'adminOnly' => true],
        ['label' => 'Settings', 'route' => 'manage.settings.index', 'pattern' => 'manage.settings.*|admin.courts.*', 'adminOnly' => true],
    ], fn (array $item) => $isAdminUser || ! $item['adminOnly']);
@endphp
<aside class="hidden lg:flex lg:flex-col lg:shrink-0 lg:justify-between bg-slate-900" style="width: 240px; padding: 24px 0;">
    <div>
        <div class="flex items-center gap-2 px-6 mb-6">
            @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-7 w-auto max-w-[160px] object-contain">
            @else
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-accent"></span>
                <span class="text-lg font-extrabold text-white">{{ $brandName }}</span>
            @endif
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
        <a href="{{ route('profile.edit') }}" class="block text-sm font-medium text-slate-400 hover:text-white mb-2">My Account</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-400 hover:text-white">Logout</button>
        </form>
    </div>
</aside>
