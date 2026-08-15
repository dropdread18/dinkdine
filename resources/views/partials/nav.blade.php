<nav class="bg-slate-900 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between flex-wrap gap-3">
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-accent"></span>
            <span class="font-extrabold text-white">Dink Dine</span>
        </a>

        <div class="flex items-center gap-1 sm:gap-4 text-sm flex-wrap">
            @auth
                @php $user = auth()->user(); @endphp

                {{-- Admins and staff get a dedicated left sidebar instead (see layouts/app.blade.php) - this partial only ever renders for customers/guests now. --}}
                <a href="{{ url('/') }}" class="text-slate-400 hover:text-white font-medium">Home</a>
                <a href="{{ route('bookings.index') }}" class="text-slate-400 hover:text-white font-medium">Book a Court</a>
                <a href="{{ route('bookings.mine') }}" class="text-slate-400 hover:text-white font-medium">My Bookings</a>
                <a href="{{ route('profile.edit') }}" class="text-slate-400 hover:text-white font-medium">Profile</a>

                <span class="hidden sm:inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-300">
                    {{ $user->name }} &middot; {{ $user->role->label() }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-white font-medium">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-slate-400 hover:text-white font-medium">Log in</a>
                <x-button tag="a" href="{{ route('register') }}" variant="primary" class="!py-2 !px-3.5 text-xs">Register</x-button>
            @endauth
        </div>
    </div>
</nav>
