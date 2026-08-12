<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PositionGroup;
use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Position::class);

        return Inertia::render('Admin/Masters/Positions', [
            'positions' => Position::query()
                ->withCount('employees')
                ->orderBy('group')
                ->orderBy('name')
                ->paginate(20),
            'groups' => collect(PositionGroup::cases())->map(fn ($group) => [
                'value' => $group->value,
                'label' => $group->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Position::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:positions,code'],
            'name' => ['required', 'string', 'max:255'],
            'group' => ['required', Rule::enum(PositionGroup::class)],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Position::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $this->authorize('update', $position);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('positions', 'code')->ignore($position)],
            'name' => ['required', 'string', 'max:255'],
            'group' => ['required', Rule::enum(PositionGroup::class)],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $position->update($data);

        return back()->with('success', __('common.updated'));
    }
}
