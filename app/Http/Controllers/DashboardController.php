<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Decree;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $payload = match ($user->role) {
            UserRole::FoundationHead, UserRole::FoundationAdmin => $this->foundationStats(),
            UserRole::UnitAdmin => $this->unitStats($user),
            UserRole::Employee => $this->employeeHome($user),
        };

        return Inertia::render('Dashboard', $payload);
    }

    /** @return array<string, mixed> */
    protected function foundationStats(): array
    {
        return [
            'totalEmployees' => Employee::query()->count(),
            'activeEmployees' => Employee::query()->where('is_active', true)->count(),
            'workUnitCount' => WorkUnit::query()->where('is_active', true)->count(),
            'pendingVerification' => Decree::query()->where('status', 'submitted')->count(),
            'pendingSignature' => Decree::query()->where('status', 'verified')->count(),
            'employeesByUnit' => DB::table('work_units')
                ->leftJoin('employees', 'employees.work_unit_id', '=', 'work_units.id')
                ->where('employees.is_active', true)
                ->groupBy('work_units.id', 'work_units.code', 'work_units.name')
                ->selectRaw('work_units.name as label, count(employees.id) as total')
                ->orderBy('work_units.code')
                ->get()
                ->map(fn (\stdClass $row) => [
                    'label' => $row->label,
                    'total' => (int) $row->total,
                ])
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    protected function unitStats(User $user): array
    {
        $total = Employee::query()->count();

        return [
            'totalEmployees' => $total,
            'activeEmployees' => Employee::query()->where('is_active', true)->count(),
            'profileCompleteness' => round($total
                ? Employee::query()->whereNotNull('phone')->count() / $total * 100 : 0),
            'pendingVerification' => Decree::query()->where('status', 'submitted')->count(),
        ];
    }

    /** @return array<string, mixed> */
    protected function employeeHome(User $user): array
    {
        $employee = $user->employee;

        return [
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'nigy' => $employee->nigy,
                'work_unit' => $employee->workUnit?->name,
                'position' => $employee->position?->name,
            ] : null,
            'recentDecree' => $employee?->decrees()
                ->where('status', 'issued')
                ->latest('signed_at')
                ->first(),
            'activeDuties' => $employee?->additionalDuties()
                ->where('status', 'active')
                ->with('additionalDuty')
                ->get(),
        ];
    }
}
