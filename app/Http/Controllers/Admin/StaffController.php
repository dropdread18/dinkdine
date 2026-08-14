<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::query()->where('role', UserRole::Staff)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(CreateStaffRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => UserRole::Staff,
        ]);

        return redirect()->route('admin.staff.index')->with('status', 'Staff account created.');
    }

    public function edit(User $staff): View
    {
        abort_unless($staff->role === UserRole::Staff, 404);

        return view('admin.staff.edit', ['staff' => $staff]);
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        abort_unless($staff->role === UserRole::Staff, 404);

        $staff->update($request->validated());

        return redirect()->route('admin.staff.index')->with('status', 'Staff account updated.');
    }

    public function toggleActive(User $staff): RedirectResponse
    {
        abort_unless($staff->role === UserRole::Staff, 404);

        $staff->update(['is_active' => ! $staff->is_active]);

        return back()->with('status', $staff->is_active ? 'Staff account enabled.' : 'Staff account disabled.');
    }
}
