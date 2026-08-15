<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Feedback session: staff/admin need to quickly answer "is Court 3 free at
 * 3pm Saturday?" over the phone without wading through the full multi-court
 * grid. Reuses AvailabilityService::forDate() (the same tested engine
 * behind the booking grid, walk-in, and reschedule flows) and just narrows
 * the result down to one court - no new availability logic.
 */
class CourtScheduleController extends Controller
{
    public function index(Request $request, AvailabilityService $availability): View
    {
        $courts = Court::orderBy('sort_order')->orderBy('court_number')->get();

        $date = $request->query('date', now()->toDateString());
        $courtId = (int) $request->query('court', $courts->first()?->id);

        $day = $availability->forDate($date);
        $courtAvailability = collect($day['courts'])->first(fn ($ca) => $ca->court->id === $courtId);

        return view('staff.courts.schedule', [
            'courts' => $courts,
            'selectedCourtId' => $courtId,
            'date' => $date,
            'isFacilityClosed' => $day['is_facility_closed'],
            'courtAvailability' => $courtAvailability,
        ]);
    }
}
