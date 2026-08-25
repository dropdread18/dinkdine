<nav class="bg-slate-900 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-6 w-auto max-w-[140px] object-contain">
            @else
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-accent"></span>
                <span class="font-extrabold text-white">{{ $brandName }}</span>
            @endif
        </a>

        {{-- Desktop: links inline. Hidden below sm, where the hamburger below takes over. --}}
        <div class="hidden sm:flex items-center gap-4 text-sm flex-wrap">
            @auth
                @php $user = auth()->user(); @endphp

                {{-- Admins and staff get a dedicated left sidebar instead (see layouts/app.blade.php) - this partial only ever renders for customers/guests now. --}}
                <a href="{{ url('/') }}" class="text-slate-400 hover:text-white font-medium">Home</a>
                <a href="{{ route('bookings.index') }}" class="text-slate-400 hover:text-white font-medium">Book a Court</a>
                <a href="{{ route('bookings.mine') }}" class="text-slate-400 hover:text-white font-medium">My Bookings</a>
                {{-- A guest/walk-in account never chose a password (password_set_at
                     is null) - Profile's only real content for them is a dead-end
                     "there's nothing to change here" message, so don't advertise it. --}}
                @if ($user->password_set_at)
                    <a href="{{ route('profile.edit') }}" class="text-slate-400 hover:text-white font-medium">Profile</a>
                @endif

                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-300">
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

        {{-- Mobile: collapses into a hamburger, same <details> pattern used by the admin/staff mobile header - no JS needed. --}}
        <details class="sm:hidden relative">
            <summary class="list-none cursor-pointer text-sm font-semibold text-white px-3 py-1.5 rounded-lg border border-white/20">Menu</summary>
            <div class="absolute right-0 mt-2 w-56 bg-slate-900 rounded-lg shadow-lg py-2 z-20 border border-white/10">
                @auth
                    @php $user = auth()->user(); @endphp
                    <a href="{{ url('/') }}" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-white/5">Home</a>
                    <a href="{{ route('bookings.index') }}" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-white/5">Book a Court</a>
                    <a href="{{ route('bookings.mine') }}" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-white/5">My Bookings</a>
                    @if ($user->password_set_at)
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-white/5">Profile</a>
                    @endif
                    <div class="border-t border-white/10 my-2"></div>
                    <div class="px-4 py-1 text-xs text-slate-500">{{ $user->name }} &middot; {{ $user->role->label() }}</div>
                    <form method="POST" action="{{ route('logout') }}" class="px-4 pt-1">
                        @csrf
                        <button type="submit" class="text-sm text-slate-400 hover:text-white py-1">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-white/5">Log in</a>
                    <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-slate-400 hover:text-white hover:bg-white/5">Register</a>
                @endauth
            </div>
        </details>
    </div>
</nav>
