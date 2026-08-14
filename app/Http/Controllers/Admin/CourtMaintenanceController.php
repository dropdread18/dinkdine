<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourtMaintenanceRequest;
use App\Models\Court;
use App\Models\CourtMaintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourtMaintenanceController extends Controller
{
    public function index(): View
    {
        return view('admin.maintenance.index', [
            'maintenancePeriods' => CourtMaintenance::query()
                ->with('court')
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.maintenance.create', ['courts' => $this->courtOptions()]);
    }

    public function store(CourtMaintenanceRequest $request): RedirectResponse
    {
        CourtMaintenance::create($request->validated());

        return redirect()->route('admin.maintenance.index')->with('status', 'Maintenance window scheduled.');
    }

    public function edit(CourtMaintenance $maintenance): View
    {
        return view('admin.maintenance.edit', [
            'maintenance' => $maintenance,
            'courts' => $this->courtOptions(),
        ]);
    }

    public function update(CourtMaintenanceRequest $request, CourtMaintenance $maintenance): RedirectResponse
    {
        $maintenance->update($request->validated());

        return redirect()->route('admin.maintenance.index')->with('status', 'Maintenance window updated.');
    }

    public function destroy(CourtMaintenance $maintenance): RedirectResponse
    {
        $maintenance->delete();

        return redirect()->route('admin.maintenance.index')->with('status', 'Maintenance window removed.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Court>
     */
    private function courtOptions()
    {
        return Court::orderBy('sort_order')->orderBy('court_number')->get();
    }
}
