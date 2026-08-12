<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmploymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmploymentStatusController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', EmploymentStatus::class);

        return Inertia::render('Admin/Masters/EmploymentStatuses', [
            'employmentStatuses' => EmploymentStatus::query()
                ->withCount('employees')
                ->orderBy('code')
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmploymentStatus::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:employment_statuses,code'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        EmploymentStatus::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, EmploymentStatus $employmentStatus): RedirectResponse
    {
        $this->authorize('update', $employmentStatus);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('employment_statuses', 'code')->ignore($employmentStatus)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $employmentStatus->update($data);

        return back()->with('success', __('common.updated'));
    }
}
