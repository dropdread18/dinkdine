<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.favicon')
        <title>Register - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --db-nav: #163732;
                --db-page-bg: #F7F3E7;
                --db-surface: #FFFFFF;
                --db-border: #E2E8F0;
                --db-ink: #0F172A;
                --db-ink-soft: #475569;
                --db-ink-faint: #64748B;
                --db-ink-faintest: #94A3B8;
                --db-accent: #A31E22;
                --db-accent-ink: #FFFFFF;
                --db-link: #2B7A46;
                --db-font: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            }
            body.db-body {
                margin: 0;
                background: var(--db-page-bg);
                color: var(--db-ink);
                font-family: var(--db-font);
                -webkit-font-smoothing: antialiased;
            }
            .db-body a { color: var(--db-link); }
            .db-body a:hover { color: var(--db-ink); }
            .db-input {
                height: 46px;
                border: 1px solid var(--db-border);
                border-radius: 8px;
                padding: 0 14px;
                font-size: 14px;
                font-family: var(--db-font);
                background: #FFFFFF;
                color: var(--db-ink);
                width: 100%;
            }
            .db-input:focus { outline: none; border-color: var(--db-link); }
        </style>
    </head>
    <body class="db-body">
        <div class="lg:flex lg:min-h-screen">
            <div class="hidden lg:flex lg:flex-1 lg:flex-col lg:justify-center gap-5 px-16" style="background: var(--db-nav);">
                <div class="flex items-center gap-2.5">
                    @if ($brandLogoUrl)
                        <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-20 w-auto max-w-[200px] object-contain">
                    @else
                        <span class="inline-block w-3 h-3 rounded-full" style="background: var(--db-accent);"></span>
                        <span class="text-[22px] font-extrabold text-white">{{ $brandName }}</span>
                    @endif
                </div>
                <div class="text-[44px] font-extrabold text-white leading-[1.1] max-w-[440px]">Book your next match in seconds.</div>
                <div class="text-base max-w-[400px]" style="color: rgba(255,255,255,0.85);">Real-time court availability, easy holds, and instant confirmations — all in one place.</div>
            </div>

            <div class="lg:w-[480px] flex flex-col" style="background: var(--db-page-bg);">
                <div class="lg:hidden flex flex-col gap-2" style="background: var(--db-nav); padding: 40px 24px 32px;">
                    <div class="flex items-center gap-2">
                        @if ($brandLogoUrl)
                            <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-12 w-auto max-w-[130px] object-contain">
                        @else
                            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: var(--db-accent);"></span>
                            <span class="text-lg font-extrabold text-white">{{ $brandName }}</span>
                        @endif
                    </div>
                    <div class="text-2xl font-extrabold text-white mt-1">Create your account</div>
                </div>

                <div class="flex-1 flex items-center justify-center px-6 py-10 lg:py-12">
                    <div class="w-full max-w-[360px] flex flex-col gap-5">
                        <div class="hidden lg:block">
                            <div class="text-[26px] font-extrabold" style="color: var(--db-ink);">Create your account</div>
                            <div class="text-sm mt-1" style="color: var(--db-ink-soft);">Join to start booking courts.</div>
                        </div>

                        @if ($errors->any())
                            <div class="text-sm rounded-xl px-4 py-3" style="background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C;">
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
                            @csrf

                            <div class="flex flex-col gap-1.5">
                                <label for="name" class="text-[13px] font-bold" style="color: var(--db-ink);">Name</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                                       placeholder="Juan Dela Cruz" class="db-input">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="email" class="text-[13px] font-bold" style="color: var(--db-ink);">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                       placeholder="you@email.com" class="db-input">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="phone" class="text-[13px] font-bold" style="color: var(--db-ink);">Phone</label>
                                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" required
                                       placeholder="0917-000-0000" class="db-input">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="password" class="text-[13px] font-bold" style="color: var(--db-ink);">Password</label>
                                <input id="password" name="password" type="password" required
                                       placeholder="••••••••" class="db-input">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="password_confirmation" class="text-[13px] font-bold" style="color: var(--db-ink);">Confirm Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required
                                       placeholder="••••••••" class="db-input">
                            </div>

                            <button type="submit" class="h-12 rounded-lg text-[15px] font-bold" style="background: var(--db-accent); color: var(--db-accent-ink);">Create Account</button>

                            <p class="text-sm text-center" style="color: var(--db-ink-soft);">
                                Already have an account? <a href="{{ route('login') }}" class="font-semibold">Log in</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
