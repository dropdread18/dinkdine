<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Owner feedback: admin/staff should immediately see pending and
        // current bookings after logging in, not land on the generic home
        // page and have to click through. redirect()->intended() still
        // takes priority - if middleware bounced them to /login from a
        // specific deep link, they return there first; this is only the
        // default when they logged in directly.
        $user = $request->user();
        $default = match (true) {
            $user->isAdmin() => route('admin.dashboard'),
            $user->isStaff() => route('staff.dashboard'),
            // No dashboard for an organizer - it's built around revenue/
            // pending-payments figures they shouldn't see. Straight to the
            // one thing they actually need: the booking schedule.
            $user->isOrganizer() => route('manage.bookings.index'),
            default => '/',
        };

        return redirect()->intended($default);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
