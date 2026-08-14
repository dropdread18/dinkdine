<nav class="bg-white border-b">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between flex-wrap gap-2">
        <a href="{{ url('/') }}" class="font-semibold text-gray-900">{{ config('app.name') }}</a>

        <div class="flex items-center gap-4 text-sm">
            @auth
                @php $user = auth()->user(); @endphp

                @if ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                    <a href="{{ route('admin.courts.index') }}" class="text-gray-700 hover:text-gray-900">Courts</a>
                    <a href="{{ route('manage.bookings.index') }}" class="text-gray-700 hover:text-gray-900">Bookings</a>
                    <a href="{{ route('manage.payments.index') }}" class="text-gray-700 hover:text-gray-900">Payments</a>
                    <a href="{{ route('manage.reports.index') }}" class="text-gray-700 hover:text-gray-900">Reports</a>
                    @foreach (['Customers', 'Staff', 'Settings'] as $item)
                        <span class="text-gray-300 cursor-not-allowed" title="Not built yet">{{ $item }}</span>
                    @endforeach
                @elseif ($user->isStaff())
                    <a href="{{ route('staff.dashboard') }}" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                    <a href="{{ route('manage.bookings.index') }}" class="text-gray-700 hover:text-gray-900">Bookings</a>
                    <a href="{{ route('manage.walkin.index') }}" class="text-gray-700 hover:text-gray-900">Walk-in Booking</a>
                    <span class="text-gray-300 cursor-not-allowed" title="Not built yet">Check-in</span>
                @else
                    <a href="{{ url('/') }}" class="text-gray-700 hover:text-gray-900">Home</a>
                    <a href="{{ route('bookings.index') }}" class="text-gray-700 hover:text-gray-900">Book a Court</a>
                    <a href="{{ route('bookings.mine') }}" class="text-gray-700 hover:text-gray-900">My Bookings</a>
                    <span class="text-gray-300 cursor-not-allowed" title="Not built yet">Profile</span>
                @endif

                <span class="text-gray-500">{{ $user->name }} &middot; {{ $user->role->label() }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-700 hover:text-gray-900">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Log in</a>
                <a href="{{ route('register') }}" class="text-gray-700 hover:text-gray-900">Register</a>
            @endauth
        </div>
    </div>
</nav>
