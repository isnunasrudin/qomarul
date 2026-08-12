<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdditionalDutyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\EmployeeAdditionalDuty;
use App\Models\WorkUnit;
use App\Services\Duty\DutyOverlapService;
use App\Support\IndonesianDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DutyAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeAdditionalDuty::class);

        $assignments = EmployeeAdditionalDuty::query()
            ->with(['employee:id,nigy,name', 'additionalDuty:id,code,name', 'workUnit:id,code,name'])
            ->when($request->filled('work_unit_id'), fn ($query) => $query->where('work_unit_id', $request->integer('work_unit_id')))
            ->when($request->filled('academic_year'), fn ($query) => $query->where('academic_year', $request->string('academic_year')))
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->orderBy('start_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Duties/Index', [
            'assignments' => $assignments,
            'filters' => $request->only(['work_unit_id', 'academic_year', 'employee_id']),
            'workUnits' => $this->visibleWorkUnits()->get(['id', 'code', 'name']),
            'academicYears' => $this->academicYears(),
            'duties' => AdditionalDuty::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'quota_per_unit']),
            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'nigy', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeAdditionalDuty::class);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'additional_duty_id' => ['required', 'integer', 'exists:additional_duties,id'],
            'work_unit_id' => ['required', 'integer', 'exists:work_units,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data['created_by'] = $request->user()->id;
        $data['status'] = AdditionalDutyStatus::Active->value;

        $overlap = app(DutyOverlapService::class)->findOverlapping(
            $data['employee_id'],
            $data['additional_duty_id'],
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
        );

        if ($overlap) {
            return back()->withErrors([
                'start_date' => 'GTK ini sudah memegang tugas yang sama pada rentang '.IndonesianDate::format($overlap->start_date).' s.d. '.IndonesianDate::format($overlap->end_date).'.',
            ])->withInput();
        }

        // indeks unik (employee_id, additional_duty_id, academic_year)
        if (EmployeeAdditionalDuty::query()
            ->where('employee_id', $data['employee_id'])
            ->where('additional_duty_id', $data['additional_duty_id'])
            ->where('academic_year', $data['academic_year'])
            ->exists()) {
            return back()->withErrors([
                'academic_year' => 'GTK ini sudah memiliki penetapan tugas yang sama pada tahun pelajaran '.$data['academic_year'].'. Gunakan periode tahun pelajaran berikutnya atau ubah penetapan yang ada.',
            ])->withInput();
        }

        EmployeeAdditionalDuty::create($data);

        return back()->with('success', $this->quotaMessage($data['additional_duty_id'], $data['work_unit_id'], $data['academic_year']));
    }

    /**
     * Penetapan massal: banyak GTK → satu referensi → satu periode (PRD F4.8).
     */
    public function storeMass(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeAdditionalDuty::class);

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'additional_duty_id' => ['required', 'integer', 'exists:additional_duties,id'],
            'work_unit_id' => ['required', 'integer', 'exists:work_units,id'],
            'academic_year' => ['required', 'string', 'max:9'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $service = app(DutyOverlapService::class);
        $created = 0;
        $skipped = [];

        foreach ($data['employee_ids'] as $employeeId) {
            $overlap = $service->findOverlapping(
                $employeeId,
                $data['additional_duty_id'],
                Carbon::parse($data['start_date']),
                Carbon::parse($data['end_date']),
            );

            if ($overlap) {
                $skipped[] = $employeeId;

                continue;
            }

            EmployeeAdditionalDuty::create([
                'employee_id' => $employeeId,
                'additional_duty_id' => $data['additional_duty_id'],
                'work_unit_id' => $data['work_unit_id'],
                'academic_year' => $data['academic_year'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'notes' => $data['notes'] ?? null,
                'status' => AdditionalDutyStatus::Active->value,
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        $message = "Penetapan selesai: {$created} GTK ditetapkan.";

        if ($skipped) {
            $message .= ' '.count($skipped).' GTK dilewati karena periode beririsan.';
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, EmployeeAdditionalDuty $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(AdditionalDutyStatus::class)],
        ]);

        $overlap = app(DutyOverlapService::class)->findOverlapping(
            $assignment->employee_id,
            $assignment->additional_duty_id,
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
            ignoreAssignmentId: $assignment->id,
        );

        if ($overlap) {
            return back()->withErrors([
                'start_date' => 'Periode beririsan dengan penetapan lain ('.IndonesianDate::format($overlap->start_date).' s.d. '.IndonesianDate::format($overlap->end_date).').',
            ])->withInput();
        }

        $assignment->update($data);

        return back()->with('success', __('common.updated'));
    }

    public function destroy(EmployeeAdditionalDuty $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return back()->with('success', __('common.deleted'));
    }

    protected function quotaMessage(int $dutyId, int $workUnitId, string $academicYear): string
    {
        $duty = AdditionalDuty::find($dutyId);

        if (! $duty?->quota_per_unit) {
            return __('common.created');
        }

        $active = EmployeeAdditionalDuty::query()
            ->where('additional_duty_id', $dutyId)
            ->where('work_unit_id', $workUnitId)
            ->where('academic_year', $academicYear)
            ->where('status', AdditionalDutyStatus::Active->value)
            ->count();

        if ($active > $duty->quota_per_unit) {
            return __('common.created')." — peringatan: kuota {$duty->name} ({$duty->quota_per_unit}) terlampaui di satuan kerja ini ({$active} penetapan aktif).";
        }

        return __('common.created');
    }

    /** @return Builder<WorkUnit> */
    protected function visibleWorkUnits(): Builder
    {
        $user = request()->user();

        if ($user->role === UserRole::UnitAdmin) {
            return WorkUnit::query()->where('id', $user->work_unit_id);
        }

        return WorkUnit::query()->where('is_active', true)->orderBy('code');
    }

    /** @return array<int, string> */
    protected function academicYears(): array
    {
        $year = (int) now()->year;

        return [$year.'/'.($year + 1), ($year - 1).'/'.$year];
    }
}
