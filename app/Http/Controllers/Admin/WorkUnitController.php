<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkUnitLevel;
use App\Http\Controllers\Controller;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkUnitController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', WorkUnit::class);

        $workUnits = WorkUnit::query()
            ->withCount('employees')
            ->orderBy('code')
            ->paginate(20);

        return Inertia::render('Admin/Masters/WorkUnits', [
            'workUnits' => $workUnits,
            'levels' => collect(WorkUnitLevel::cases())->map(fn ($level) => [
                'value' => $level->value,
                'label' => $level->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', WorkUnit::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:work_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', Rule::enum(WorkUnitLevel::class)],
            'npsn' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'head_name' => ['nullable', 'string', 'max:255'],
            'head_nigy' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        WorkUnit::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, WorkUnit $workUnit): RedirectResponse
    {
        $this->authorize('update', $workUnit);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('work_units', 'code')->ignore($workUnit)],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', Rule::enum(WorkUnitLevel::class)],
            'npsn' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'head_name' => ['nullable', 'string', 'max:255'],
            'head_nigy' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $workUnit->update($data);

        return back()->with('success', __('common.updated'));
    }
}
