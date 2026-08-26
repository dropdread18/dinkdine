<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A pure, read-only view of court availability - same underlying data as
 * Walk-in Booking's grid, but nothing is clickable. Built for the
 * Organizer role, which needs to see what's available/booked/Open Play
 * per court without gaining a way to create or touch a booking itself.
 */
class ScheduleController extends Controller
{
    public function index(Request $request, AvailabilityService $availability): View
    {
        $date = $request->query('date', now()->toDateString());
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('staff.schedule.index', [
            'date' => $date,
            'availability' => $availability->forDate($date),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }
}
