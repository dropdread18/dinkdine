<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpenPlaySessionRequest;
use App\Models\Court;
use App\Models\OpenPlaySession;
use Illuminate\Http\RedirectResponse;
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

    public function create(): View
    {
        return view('admin.open-play.create', ['courts' => $this->courtOptions()]);
    }

    public function store(OpenPlaySessionRequest $request): RedirectResponse
    {
        OpenPlaySession::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.open-play.index')->with('status', 'Open Play session scheduled.');
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
