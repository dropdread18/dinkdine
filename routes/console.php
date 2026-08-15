<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requirements.md §32. Runs often enough that a booking made shortly
// before its 24h/1h mark still gets a reminder - the command itself is
// idempotent, so more-frequent runs are cheap and safe, not risky.
Schedule::command('bookings:send-reminders')->everyFifteenMinutes();

// Guest-checkout payment-hold flow (feedback session): a 10-minute window
// needs much tighter granularity than the reminder sweep above, or most
// of the hold would already be gone before the sweep ran even once.
Schedule::command('bookings:expire-payment-holds')->everyMinute();
