<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkUnitLevel;
use App\Http\Controllers\Controller;
use App\Models\AdditionalDuty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdditionalDutyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AdditionalDuty::class);

        return Inertia::render('Admin/Masters/AdditionalDuties', [
            'additionalDuties' => AdditionalDuty::query()
                ->withCount('assignments')
                ->orderBy('name')
                ->paginate(20),
            'levels' => collect(WorkUnitLevel::cases())->map(fn ($level) => [
                'value' => $level->value,
                'label' => $level->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AdditionalDuty::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:additional_duties,code'],
            'name' => ['required', 'string', 'max:255'],
            'applicable_levels' => ['nullable', 'string'],
            'hour_equivalence' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'quota_per_unit' => ['nullable', 'integer', 'min:1'],
            'requires_decree' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['applicable_levels'] = $this->parseLevels($data['applicable_levels'] ?? null);
        $data['requires_decree'] = $request->boolean('requires_decree');
        $data['is_active'] = $request->boolean('is_active', true);

        AdditionalDuty::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, AdditionalDuty $additionalDuty): RedirectResponse
    {
        $this->authorize('update', $additionalDuty);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('additional_duties', 'code')->ignore($additionalDuty)],
            'name' => ['required', 'string', 'max:255'],
            'applicable_levels' => ['nullable', 'string'],
            'hour_equivalence' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'quota_per_unit' => ['nullable', 'integer', 'min:1'],
            'requires_decree' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['applicable_levels'] = $this->parseLevels($data['applicable_levels'] ?? null);
        $data['requires_decree'] = $request->boolean('requires_decree');
        $data['is_active'] = $request->boolean('is_active', true);

        $additionalDuty->update($data);

        return back()->with('success', __('common.updated'));
    }

    /** @return array<int, string>|null */
    protected function parseLevels(?string $levels): ?array
    {
        $values = array_values(array_filter(array_map('trim', explode(',', (string) $levels))));

        foreach ($values as $value) {
            if (! in_array($value, WorkUnitLevel::values(), true)) {
                abort(422, "Jenjang tidak dikenal: {$value}");
            }
        }

        return $values ?: null;
    }
}
