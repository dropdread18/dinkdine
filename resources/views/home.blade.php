@extends('layouts.app', ['title' => 'Home'])

@section('content')
    @php
        $facilityName = \App\Models\Setting::get('facility_name');
        $facilityAddress = \App\Models\Setting::get('facility_address');
        $facilityPhone = \App\Models\Setting::get('facility_phone');
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $hoursByDay = $businessHours->keyBy('day_of_week');
    @endphp

    <div class="rounded-3xl overflow-hidden bg-slate-900 mb-10">
        <div class="px-6 py-14 sm:px-12 sm:py-20 max-w-2xl">
            <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-accent">
                <span class="inline-block w-2 h-2 rounded-full bg-accent"></span>
                {{ $facilityName }}
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight mt-4 leading-tight">
                Book your court in seconds.
            </h1>
            <p class="text-slate-300 mt-4 text-base sm:text-lg leading-relaxed">
                See real-time availability, pick your slot, and pay online — no phone calls, no waiting for a callback. Walk-ins are always welcome too.
            </p>
            <div class="flex flex-wrap gap-3 mt-8">
                <x-button tag="a" href="{{ route('bookings.index') }}" class="!text-base !px-6 !py-3">Book a Court</x-button>
                @guest
                    <x-button tag="a" href="{{ route('register') }}" variant="secondary" class="!bg-transparent !text-white !border-white/30 hover:!bg-white/10 !text-base !px-6 !py-3">Create an Account</x-button>
                @endguest
            </div>
        </div>
    </div>

    @auth
        <x-card class="max-w-xl mb-10">
            <h2 class="text-lg font-semibold text-slate-900">Welcome back, {{ auth()->user()->name }}</h2>
            <p class="text-slate-600 mt-1 text-sm leading-relaxed">
                @if (auth()->user()->isAdmin())
                    Head to your <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">dashboard</a> to see today's activity.
                @elseif (auth()->user()->isStaff())
                    Head to your <a href="{{ route('staff.dashboard') }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">dashboard</a> to see today's activity.
                @else
                    Ready for your next game? <a href="{{ route('bookings.mine') }}" class="text-blue-600 hover:text-blue-700 underline underline-offset-2">View your bookings</a>.
                @endif
            </p>
        </x-card>
    @endauth

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <div class="lg:col-span-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-4">Our Courts</h2>
            @if ($courts->isEmpty())
                <x-card class="text-sm text-slate-500">Court information is coming soon — check back shortly.</x-card>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($courts as $court)
                        <x-card class="!p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $court->name }}</div>
                                    @if ($court->court_type)
                                        <div class="text-xs text-slate-500 mt-0.5">{{ $court->court_type }}</div>
                                    @endif
                                </div>
                                <div class="text-sm font-bold text-slate-900 whitespace-nowrap">₱{{ number_format($court->hourly_rate, 0) }}<span class="font-normal text-slate-500">/hr</span></div>
                            </div>
                            @if ($court->description)
                                <p class="text-sm text-slate-600 mt-2 leading-relaxed">{{ $court->description }}</p>
                            @endif
                            @if ($court->location)
                                <p class="text-xs text-slate-400 mt-2">{{ $court->location }}</p>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-4">Hours &amp; Location</h2>
            <x-card class="!p-5 space-y-4">
                <div class="space-y-1.5">
                    @foreach ($dayNames as $day => $label)
                        @php $hour = $hoursByDay->get($day); @endphp
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">{{ $label }}</span>
                            <span class="text-slate-900 font-medium">
                                @if (! $hour || $hour->is_closed)
                                    Closed
                                @else
                                    {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $hour->opens_at)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $hour->closes_at)->format('g:i A') }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                @if ($facilityAddress || $facilityPhone)
                    <div class="pt-3 border-t border-slate-100 space-y-1 text-sm text-slate-600">
                        @if ($facilityAddress)
                            <div>{{ $facilityAddress }}</div>
                        @endif
                        @if ($facilityPhone)
                            <div>{{ $facilityPhone }}</div>
                        @endif
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <div>
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-4">How It Works</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card class="!p-5">
                <div class="text-xs font-bold text-slate-900 bg-accent inline-flex items-center justify-center w-7 h-7 rounded-full">1</div>
                <div class="font-semibold text-slate-900 mt-3">Pick a slot</div>
                <p class="text-sm text-slate-600 mt-1 leading-relaxed">Browse real-time court availability by date and time — no account required to look.</p>
            </x-card>
            <x-card class="!p-5">
                <div class="text-xs font-bold text-slate-900 bg-accent inline-flex items-center justify-center w-7 h-7 rounded-full">2</div>
                <div class="font-semibold text-slate-900 mt-3">Confirm &amp; pay</div>
                <p class="text-sm text-slate-600 mt-1 leading-relaxed">Your slot is held while you pay via GCash or bank transfer, then it's yours.</p>
            </x-card>
            <x-card class="!p-5">
                <div class="text-xs font-bold text-slate-900 bg-accent inline-flex items-center justify-center w-7 h-7 rounded-full">3</div>
                <div class="font-semibold text-slate-900 mt-3">Show up &amp; play</div>
                <p class="text-sm text-slate-600 mt-1 leading-relaxed">Bring your reference number or receipt — our staff will check you in at the court.</p>
            </x-card>
        </div>
    </div>
@endsection
