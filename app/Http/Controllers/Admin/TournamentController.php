<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TournamentRequest;
use App\Models\Court;
use App\Models\Setting;
use App\Models\Tournament;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(): View
    {
        return view('admin.tournaments.index', [
            'tournaments' => Tournament::query()
                ->with('court')
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    /**
     * Same reasoning as TrainingSessionController::create() - the form
     * ships with the same read-only availability grid so whoever is
     * scheduling a Tournament can check what's free without leaving
     * the page.
     */
    public function create(Request $request, AvailabilityService $availability): View
    {
        $date = $request->query('date', now()->toDateString());
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('admin.tournaments.create', [
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
            'date' => $date,
            'availability' => $availability->forDate($date),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }

    public function store(TournamentRequest $request): RedirectResponse
    {
        Tournament::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.tournaments.index')->with('status', 'Tournament scheduled.');
    }

    public function edit(Tournament $tournament): View
    {
        return view('admin.tournaments.edit', [
            'tournament' => $tournament,
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
        ]);
    }

    public function update(TournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $tournament->update($request->validated());

        return redirect()->route('admin.tournaments.index')->with('status', 'Tournament updated.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $tournament->delete();

        return redirect()->route('admin.tournaments.index')->with('status', 'Tournament removed.');
    }
}
