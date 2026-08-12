<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Decree;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\Employee\ProfileCompletenessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
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
            'queueAges' => [
                'verification' => $this->queueAge(Decree::query()->where('status', 'submitted')),
                'signature' => $this->queueAge(Decree::query()->where('status', 'verified')),
            ],
            'employeesByUnit' => DB::table('work_units')
                ->leftJoin('employees', 'employees.work_unit_id', '=', 'work_units.id')
                ->where('employees.is_active', true)
                ->groupBy('work_units.id', 'work_units.code', 'work_units.name')
                ->selectRaw('work_units.name as label, count(employees.id) as total')
                ->orderBy('work_units.code')
                ->get()
                ->map(fn (\stdClass $row) => ['label' => $row->label, 'total' => (int) $row->total])
                ->all(),
            'employeesByPosition' => $this->employeesByField('positions', 'name'),
            'employeesByStatus' => $this->employeesByField('employment_statuses', 'name'),
            'employeesByEducation' => Employee::query()
                ->where('is_active', true)
                ->whereHas('educations', fn ($query) => $query->where('is_highest', true))
                ->with('educations')
                ->get()
                ->countBy(fn (Employee $e) => ($education = $e->educations()->where('is_highest', true)->first())
                ? ($education->level->value ?? '—')
                : '—')
                ->map(fn ($count, $label) => ['label' => $label, 'total' => $count])
                ->values()
                ->all(),
            'recentDecrees' => Decree::query()
                ->with(['employee:id,name,nigy', 'decreeType:id,name'])
                ->latest('created_at')
                ->limit(6)
                ->get(),
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
            'pendingSignature' => Decree::query()->where('status', 'verified')->count(),
            'employeesByPosition' => $this->employeesByField('positions', 'name'),
            'recentDecrees' => Decree::query()
                ->with(['employee:id,name,nigy', 'decreeType:id,name'])
                ->latest('created_at')
                ->limit(6)
                ->get(),
        ];
    }

    /** @return array<string, mixed> */
    protected function employeeHome(User $user): array
    {
        $employee = $user->employee;

        $recentDecrees = $employee
            ? $employee->decrees()
                ->where('status', 'issued')
                ->where(function ($query) {
                    $query->where('is_legacy', false)
                        ->orWhere(function ($query) {
                            $query->where('is_legacy', true)->whereNotNull('legacy_verified_at');
                        });
                })
                ->latest('signed_at')
                ->limit(5)
                ->get(['id', 'decree_number', 'decree_type_id', 'effective_date', 'issued_date', 'status', 'is_legacy', 'uuid'])
                ->load('decreeType:id,name')
            : collect();

        $recentDecrees->each(function (Decree $decree): void {
            if (! $decree->is_legacy) {
                $decree->download_url = URL::temporarySignedRoute(
                    'portal.decrees.download',
                    now()->addHour(),
                    ['decree' => $decree->id],
                );
            }
        });

        return [
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'nigy' => $employee->nigy,
                'work_unit' => $employee->workUnit?->name,
                'position' => $employee->position?->name,
            ] : null,
            'completeness' => $employee ? app(ProfileCompletenessService::class)->evaluate($employee) : null,
            'recentDecree' => $employee?->decrees()
                ->where('status', 'issued')
                ->latest('signed_at')
                ->first(),
            'activeDuties' => $employee?->additionalDuties()
                ->where('status', 'active')
                ->with('additionalDuty:id,name')
                ->get(),
            'recentDecrees' => $recentDecrees,
        ];
    }

    /** @return array<int, array{label: string, total: int}> */
    protected function employeesByField(string $table, string $column): array
    {
        $related = $table === 'positions' ? 'position' : 'employmentStatus';

        return Employee::query()
            ->where('is_active', true)
            ->with("{$related}:id,{$column}")
            ->get()
            ->countBy(fn (Employee $e) => ($value = $e->{$related}?->{$column}) ? $value : '—')
            ->map(fn ($count, $label) => ['label' => $label, 'total' => $count])
            ->values()
            ->all();
    }

    /** Umur antrean: hari sejak SK tertua dalam antrean.
     *
     * @param  Builder<Decree>  $query
     */
    protected function queueAge($query): ?int
    {
        $oldest = (clone $query)->min('created_at');

        return $oldest ? (int) Carbon::parse($oldest)->diffInDays(now()) : null;
    }
}
