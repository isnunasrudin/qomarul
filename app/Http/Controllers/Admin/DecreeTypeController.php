<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DecreeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DecreeTypeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', DecreeType::class);

        return Inertia::render('Admin/Masters/DecreeTypes', [
            'decreeTypes' => DecreeType::query()
                ->withCount('decrees')
                ->orderBy('code')
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DecreeType::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:decree_types,code'],
            'name' => ['required', 'string', 'max:255'],
            'template_view' => ['nullable', 'string', 'max:255'],
            'number_format' => ['nullable', 'string', 'max:255'],
            'number_padding' => ['nullable', 'integer', 'min:1', 'max:10'],
            'consideration_recalling' => ['nullable', 'string'],
            'consideration_weighing' => ['nullable', 'string'],
            'consideration_observing' => ['nullable', 'string'],
            'requires_effective_date' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['consideration_weighing'] = $this->parseLines($data['consideration_weighing'] ?? null);
        $data['number_padding'] = $data['number_padding'] ?? 3;
        $data['requires_effective_date'] = $request->boolean('requires_effective_date', true);
        $data['is_active'] = $request->boolean('is_active', true);

        DecreeType::create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, DecreeType $decreeType): RedirectResponse
    {
        $this->authorize('update', $decreeType);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('decree_types', 'code')->ignore($decreeType)],
            'name' => ['required', 'string', 'max:255'],
            'template_view' => ['nullable', 'string', 'max:255'],
            'number_format' => ['nullable', 'string', 'max:255'],
            'number_padding' => ['nullable', 'integer', 'min:1', 'max:10'],
            'consideration_recalling' => ['nullable', 'string'],
            'consideration_weighing' => ['nullable', 'string'],
            'consideration_observing' => ['nullable', 'string'],
            'requires_effective_date' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['consideration_weighing'] = $this->parseLines($data['consideration_weighing'] ?? null);
        $data['number_padding'] = $data['number_padding'] ?? 3;
        $data['requires_effective_date'] = $request->boolean('requires_effective_date', true);
        $data['is_active'] = $request->boolean('is_active', true);

        $decreeType->update($data);

        return back()->with('success', __('common.updated'));
    }

    /** @return array<int, string>|null */
    protected function parseLines(?string $lines): ?array
    {
        $values = array_values(array_filter(array_map('trim', explode("\n", (string) $lines))));

        return $values ?: null;
    }
}
