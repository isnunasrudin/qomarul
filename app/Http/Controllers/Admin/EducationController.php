<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationLevel;
use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EducationController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('create', Education::class);

        $data = $request->validate([
            'level' => ['required', Rule::enum(EducationLevel::class)],
            'institution' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'start_year' => ['nullable', 'digits:4'],
            'end_year' => ['nullable', 'digits:4'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'certificate_date' => ['nullable', 'date'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'is_highest' => ['boolean'],
        ]);

        if ($request->boolean('is_highest')) {
            $employee->educations()->update(['is_highest' => false]);
        }

        $employee->educations()->create($data);

        return back()->with('success', __('common.created'));
    }

    public function update(Request $request, Employee $employee, Education $education): RedirectResponse
    {
        $this->authorize('update', $education);

        $data = $request->validate([
            'level' => ['required', Rule::enum(EducationLevel::class)],
            'institution' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'start_year' => ['nullable', 'digits:4'],
            'end_year' => ['nullable', 'digits:4'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'certificate_date' => ['nullable', 'date'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'is_highest' => ['boolean'],
        ]);

        if ($request->boolean('is_highest')) {
            $employee->educations()->where('id', '!=', $education->id)->update(['is_highest' => false]);
        }

        $education->update($data);

        return back()->with('success', __('common.updated'));
    }

    public function destroy(Education $education): RedirectResponse
    {
        $this->authorize('delete', $education);

        $education->delete();

        return back()->with('success', __('common.deleted'));
    }
}
