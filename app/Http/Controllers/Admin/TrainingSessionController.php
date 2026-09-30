<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingSessionRequest;
use App\Models\Court;
use App\Models\Setting;
use App\Models\TrainingSession;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingSessionController extends Controller
{
    public function index(): View
    {
        return view('admin.training-sessions.index', [
            'sessions' => TrainingSession::query()
                ->with('court')
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    /**
     * Same reasoning as OpenPlaySessionController::create() - the form
     * ships with the same read-only availability grid so whoever is
     * scheduling a Training Session can check what's free without
     * leaving the page.
     */
    public function create(Request $request, AvailabilityService $availability): View
    {
        $date = $request->query('date', now()->toDateString());
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('admin.training-sessions.create', [
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
            'date' => $date,
            'availability' => $availability->forDate($date),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }

    public function store(TrainingSessionRequest $request): RedirectResponse
    {
        TrainingSession::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.training-sessions.index')->with('status', 'Training Session scheduled.');
    }

    public function edit(TrainingSession $session): View
    {
        return view('admin.training-sessions.edit', [
            'session' => $session,
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
        ]);
    }

    public function update(TrainingSessionRequest $request, TrainingSession $session): RedirectResponse
    {
        $session->update($request->validated());

        return redirect()->route('admin.training-sessions.index')->with('status', 'Training Session updated.');
    }

    public function destroy(TrainingSession $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('admin.training-sessions.index')->with('status', 'Training Session removed.');
    }
}
