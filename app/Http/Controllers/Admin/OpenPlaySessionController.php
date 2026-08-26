<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpenPlaySessionRequest;
use App\Models\Court;
use App\Models\OpenPlaySession;
use App\Models\Setting;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpenPlaySessionController extends Controller
{
    public function index(): View
    {
        return view('admin.open-play.index', [
            'sessions' => OpenPlaySession::query()
                ->with('court')
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    /**
     * The create form ships with the same read-only availability grid the
     * Organizer's Schedule page shows, so whoever is booking Open Play can
     * check what's free without leaving the page - no more flipping
     * between two tabs to compare the schedule against the form.
     */
    public function create(Request $request, AvailabilityService $availability): View
    {
        $date = $request->query('date', now()->toDateString());
        $maxAdvanceDays = (int) (Setting::get('max_advance_booking_days') ?? 30);

        return view('admin.open-play.create', [
            'courts' => $this->courtOptions(),
            'date' => $date,
            'availability' => $availability->forDate($date),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays($maxAdvanceDays)->toDateString(),
        ]);
    }

    public function store(OpenPlaySessionRequest $request): RedirectResponse
    {
        $courtIds = $request->validated('court_ids');
        $shared = collect($request->validated())->except('court_ids')->all();

        foreach ($courtIds as $courtId) {
            OpenPlaySession::create([...$shared, 'court_id' => $courtId, 'created_by' => $request->user()->id]);
        }

        $status = count($courtIds) === 1
            ? 'Open Play session scheduled.'
            : count($courtIds).' Open Play sessions scheduled.';

        return redirect()->route('admin.open-play.index')->with('status', $status);
    }

    public function edit(OpenPlaySession $session): View
    {
        return view('admin.open-play.edit', [
            'session' => $session,
            'courts' => $this->courtOptions(),
        ]);
    }

    public function update(OpenPlaySessionRequest $request, OpenPlaySession $session): RedirectResponse
    {
        $session->update($request->validated());

        return redirect()->route('admin.open-play.index')->with('status', 'Open Play session updated.');
    }

    public function destroy(OpenPlaySession $session): RedirectResponse
    {
        $session->delete();

        return redirect()->route('admin.open-play.index')->with('status', 'Open Play session removed.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Court>
     */
    private function courtOptions()
    {
        return Court::orderBy('sort_order')->orderBy('court_number')->get();
    }
}
