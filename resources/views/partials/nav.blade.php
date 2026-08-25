<nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
        <a href="{{ url('/') }}" class="flex items-center gap-2 shrink-0">
            @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-6 w-auto max-w-[140px] object-contain">
            @else
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-accent"></span>
                <span class="font-extrabold text-slate-900">{{ $brandName }}</span>
            @endif
        </a>

        {{-- Desktop: links inline. Hidden below sm, where the hamburger below takes over. --}}
        <div class="hidden sm:flex items-center gap-4 text-sm flex-wrap">
            @auth
                @php $user = auth()->user(); @endphp

                {{-- Admins and staff get a dedicated left sidebar instead (see layouts/app.blade.php) - this partial only ever renders for customers/guests now. --}}
                <a href="{{ url('/') }}" class="text-slate-500 hover:text-slate-900 font-medium">Home</a>
                <a href="{{ route('bookings.index') }}" class="text-slate-500 hover:text-slate-900 font-medium">Book a Court</a>
                <a href="{{ route('bookings.mine') }}" class="text-slate-500 hover:text-slate-900 font-medium">My Bookings</a>
                {{-- A guest/walk-in account never chose a password (password_set_at
                     is null) - Profile's only real content for them is a dead-end
                     "there's nothing to change here" message, so don't advertise it. --}}
                @if ($user->password_set_at)
                    <a href="{{ route('profile.edit') }}" class="text-slate-500 hover:text-slate-900 font-medium">Profile</a>
                @endif
                <a href="{{ route('contact') }}" class="text-slate-500 hover:text-slate-900 font-medium">Contact</a>

                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                    {{ $user->name }} &middot; {{ $user->role->label() }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-slate-900 font-medium">Logout</button>
                </form>
            @else
                <a href="{{ route('contact') }}" class="text-slate-500 hover:text-slate-900 font-medium">Contact</a>
                <a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-900 font-medium">Log in</a>
                <x-button tag="a" href="{{ route('register') }}" variant="primary" class="!py-2 !px-3.5 text-xs">Register</x-button>
            @endauth
        </div>

        {{-- Mobile: collapses into a hamburger, same <details> pattern used by the admin/staff mobile header - no JS needed. --}}
        <details class="sm:hidden relative">
            <summary class="list-none cursor-pointer text-sm font-semibold text-slate-900 px-3 py-1.5 rounded-lg border border-slate-300">Menu</summary>
            <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 z-20 border border-slate-200">
                @auth
                    @php $user = auth()->user(); @endphp
                    <a href="{{ url('/') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Home</a>
                    <a href="{{ route('bookings.index') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Book a Court</a>
                    <a href="{{ route('bookings.mine') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">My Bookings</a>
                    @if ($user->password_set_at)
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Profile</a>
                    @endif
                    <a href="{{ route('contact') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Contact</a>
                    <div class="border-t border-slate-200 my-2"></div>
                    <div class="px-4 py-1 text-xs text-slate-400">{{ $user->name }} &middot; {{ $user->role->label() }}</div>
                    <form method="POST" action="{{ route('logout') }}" class="px-4 pt-1">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 hover:text-slate-900 py-1">Logout</button>
                    </form>
                @else
                    <a href="{{ route('contact') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Contact</a>
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Log in</a>
                    <a href="{{ route('register') }}" class="block px-4 py-2 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-50">Register</a>
                @endauth
            </div>
        </details>
    </div>
</nav>
