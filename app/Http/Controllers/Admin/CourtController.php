<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourtRequest;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourtController extends Controller
{
    public function index(): View
    {
        return view('admin.courts.index', [
            'courts' => Court::orderBy('sort_order')->orderBy('court_number')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.courts.create');
    }

    public function store(CourtRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('courts', 'public');
        }

        Court::create($data);

        return redirect()->route('admin.courts.index')->with('status', 'Court created.');
    }

    public function edit(Court $court): View
    {
        return view('admin.courts.edit', ['court' => $court]);
    }

    public function update(CourtRequest $request, Court $court): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($court->image) {
                Storage::disk('public')->delete($court->image);
            }
            $data['image'] = $request->file('image')->store('courts', 'public');
        }

        $court->update($data);

        return redirect()->route('admin.courts.index')->with('status', 'Court updated.');
    }

    public function destroy(Court $court): RedirectResponse
    {
        if ($court->bookings()->exists()) {
            return back()->withErrors([
                'court' => "\"{$court->name}\" has bookings and can't be deleted. Set it to Inactive or Closed instead.",
            ]);
        }

        if ($court->image) {
            Storage::disk('public')->delete($court->image);
        }

        $court->delete();

        return redirect()->route('admin.courts.index')->with('status', 'Court deleted.');
    }
}
