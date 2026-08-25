@extends('layouts.app', ['title' => 'Contact'])

@section('content')
    @php
        $facilityName = \App\Models\Setting::get('facility_name');
        $facilityAddress = \App\Models\Setting::get('facility_address');
        $facilityPhone = \App\Models\Setting::get('facility_phone');
        $facilityEmail = \App\Models\Setting::get('facility_email');
        $facilityFacebook = \App\Models\Setting::get('facility_facebook');
        $openPlayLink = \App\Models\Setting::get('open_play_link');
    @endphp

    <div class="rounded-3xl overflow-hidden bg-slate-900 mb-10">
        <div class="px-6 py-14 sm:px-12 sm:py-20 max-w-2xl">
            <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-accent">
                <span class="inline-block w-2 h-2 rounded-full bg-accent"></span>
                {{ $facilityName }}
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight mt-4 leading-tight">
                Get in touch.
            </h1>
            <p class="text-slate-300 mt-4 text-base sm:text-lg leading-relaxed">
                Questions about booking, walk-ins, or Open Play? Reach us directly, or find us on the court.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-4">Find Us</h2>
            <x-card class="!p-0 overflow-hidden">
                <iframe
                    src="https://www.google.com/maps?q=7.0874851,125.5864049&z=17&output=embed"
                    class="w-full h-[360px] border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="Map showing {{ $facilityName }}'s location"></iframe>
            </x-card>
        </div>

        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500 mb-4">Contact Details</h2>
            <x-card class="!p-5 space-y-4">
                @if ($facilityAddress)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Address</div>
                        <div class="text-sm text-slate-900 mt-1">{{ $facilityAddress }}</div>
                    </div>
                @endif

                @if ($facilityPhone)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Phone</div>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $facilityPhone) }}" class="text-sm text-blue-600 hover:text-blue-700 underline underline-offset-2 mt-1 inline-block">{{ $facilityPhone }}</a>
                    </div>
                @endif

                @if ($facilityEmail)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Email</div>
                        <a href="mailto:{{ $facilityEmail }}" class="text-sm text-blue-600 hover:text-blue-700 underline underline-offset-2 mt-1 inline-block">{{ $facilityEmail }}</a>
                    </div>
                @endif

                @if ($facilityFacebook)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Facebook</div>
                        <a href="{{ $facilityFacebook }}" target="_blank" rel="noopener" class="text-sm text-blue-600 hover:text-blue-700 underline underline-offset-2 mt-1 inline-block">Visit our Page</a>
                    </div>
                @endif

                @if ($openPlayLink)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Open Play</div>
                        <a href="{{ $openPlayLink }}" target="_blank" rel="noopener" class="text-sm text-blue-600 hover:text-blue-700 underline underline-offset-2 mt-1 inline-block">See Open Play Sessions</a>
                    </div>
                @endif

                @unless ($facilityAddress || $facilityPhone || $facilityEmail || $facilityFacebook || $openPlayLink)
                    <p class="text-sm text-slate-500">Contact details haven't been added yet.</p>
                @endunless
            </x-card>
        </div>
    </div>
@endsection
