<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->with(['workUnit', 'employee:id,id,nigy,name'])
                ->orderBy('name')
                ->paginate(20),
            'roles' => collect(UserRole::cases())->map(fn ($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'workUnits' => WorkUnit::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'work_unit_id' => ['nullable', 'integer', 'exists:work_units,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'must_change_password' => ['boolean'],
        ]);

        $data['role'] = UserRole::from($data['role']);
        $data['must_change_password'] = $request->boolean('must_change_password', true);

        if ($data['role'] === UserRole::UnitAdmin && ! $data['work_unit_id']) {
            return back()->withErrors(['work_unit_id' => __('validation.required')])->withInput();
        }

        if ($data['role'] === UserRole::Employee && ! $data['employee_id']) {
            return back()->withErrors(['employee_id' => __('validation.required')])->withInput();
        }

        User::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'work_unit_id' => ['nullable', 'integer', 'exists:work_units,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'is_active' => ['boolean'],
        ]);

        $data['role'] = UserRole::from($data['role']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user->update($data);

        return back()->with('success', __('common.updated'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => $data['password'],
            'must_change_password' => true,
        ]);

        return back()->with('success', __('common.updated'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', __('common.updated'));
    }
}
