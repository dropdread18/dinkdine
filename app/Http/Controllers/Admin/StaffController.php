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
    /**
     * @var list<UserRole> Both roles managed on this one screen - an admin
     *                     account is just a staff account with more
     *                     access, and reusing this existing, already
     *                     admin-only-gated page is a smaller, safer change
     *                     than building a second parallel account-creation
     *                     flow from scratch.
     */
    private const MANAGED_ROLES = [UserRole::Staff, UserRole::Admin];

    public function index(): View
    {
        return view('admin.staff.index', [
            'staff' => User::query()->whereIn('role', self::MANAGED_ROLES)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create');
    }

    public function store(CreateStaffRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->except('role'),
            'role' => UserRole::from($request->validated('role') ?? UserRole::Staff->value),
        ]);

        return redirect()->route('admin.staff.index')->with('status', 'Account created.');
    }

    public function edit(User $staff): View
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES, true), 404);

        return view('admin.staff.edit', ['staff' => $staff]);
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES, true), 404);

        $staff->update($request->validated());

        return redirect()->route('admin.staff.index')->with('status', 'Account updated.');
    }

    public function toggleActive(User $staff): RedirectResponse
    {
        abort_unless(in_array($staff->role, self::MANAGED_ROLES, true), 404);

        if ($staff->is(auth()->user())) {
            return back()->withErrors(['staff' => 'You cannot disable your own account.']);
        }

        $staff->update(['is_active' => ! $staff->is_active]);

        return back()->with('status', $staff->is_active ? 'Account enabled.' : 'Account disabled.');
    }
}
