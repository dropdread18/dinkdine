<nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between flex-wrap gap-3">
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-white text-xs font-semibold">PB</span>
            <span class="font-semibold text-slate-900">{{ config('app.name') }}</span>
        </a>

        <div class="flex items-center gap-1 sm:gap-4 text-sm flex-wrap">
            @auth
                @php $user = auth()->user(); @endphp

                @if ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 font-medium">Dashboard</a>
                    <a href="{{ route('admin.courts.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Courts</a>
                    <a href="{{ route('admin.maintenance.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Maintenance</a>
                    <a href="{{ route('manage.courts.schedule') }}" class="text-slate-600 hover:text-slate-900 font-medium">Court Schedule</a>
                    <a href="{{ route('manage.bookings.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Bookings</a>
                    <a href="{{ route('manage.payments.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Payments</a>
                    <a href="{{ route('manage.reports.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Reports</a>
                    <a href="{{ route('manage.settings.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Settings</a>
                    <a href="{{ route('admin.customers.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Customers</a>
                    <a href="{{ route('admin.staff.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Staff</a>
                @elseif ($user->isStaff())
                    <a href="{{ route('staff.dashboard') }}" class="text-slate-600 hover:text-slate-900 font-medium">Dashboard</a>
                    <a href="{{ route('manage.courts.schedule') }}" class="text-slate-600 hover:text-slate-900 font-medium">Court Schedule</a>
                    <a href="{{ route('manage.bookings.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Bookings</a>
                    <a href="{{ route('manage.walkin.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Walk-in Booking</a>
                    <a href="{{ route('manage.checkin.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Check-in</a>
                @else
                    <a href="{{ url('/') }}" class="text-slate-600 hover:text-slate-900 font-medium">Home</a>
                    <a href="{{ route('bookings.index') }}" class="text-slate-600 hover:text-slate-900 font-medium">Book a Court</a>
                    <a href="{{ route('bookings.mine') }}" class="text-slate-600 hover:text-slate-900 font-medium">My Bookings</a>
                    <a href="{{ route('profile.edit') }}" class="text-slate-600 hover:text-slate-900 font-medium">Profile</a>
                @endif

                <span class="hidden sm:inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                    {{ $user->name }} &middot; {{ $user->role->label() }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-slate-900 font-medium">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900 font-medium">Log in</a>
                <x-button tag="a" href="{{ route('register') }}" variant="primary" class="!py-2 !px-3.5 text-xs">Register</x-button>
            @endauth
        </div>
    </div>
</nav>
