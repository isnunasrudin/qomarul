<?php

namespace App\Services\User;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pembuatan akun portal GTK (PRD F1.2a): username = NIGY, kata sandi awal
 * acak, wajib ganti pada masuk pertama.
 */
class UserAccountService
{
    public function createForEmployee(Employee $employee): User
    {
        $password = Str::password(10, symbols: true);

        return User::create([
            'name' => $employee->full_name,
            'username' => $employee->nigy,
            'email' => $employee->email,
            'password' => Hash::make($password),
            'role' => UserRole::Employee,
            'work_unit_id' => $employee->work_unit_id,
            'employee_id' => $employee->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }

    /**
     * Buat akun untuk seluruh GTK aktif yang belum punya akun.
     *
     * @return array{created: array<int, array{name: string, username: string, password: string, work_unit_code: string}>, skipped: int}
     */
    public function createForAllMissing(?int $workUnitId = null): array
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->whereDoesntHave('user')
            ->when($workUnitId, fn ($query) => $query->where('work_unit_id', $workUnitId))
            ->with('workUnit:id,code')
            ->get();

        $created = [];

        foreach ($employees as $employee) {
            $password = Str::password(10, symbols: true);

            User::create([
                'name' => $employee->full_name,
                'username' => $employee->nigy,
                'email' => $employee->email,
                'password' => Hash::make($password),
                'role' => UserRole::Employee,
                'work_unit_id' => $employee->work_unit_id,
                'employee_id' => $employee->id,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $created[] = [
                'name' => $employee->full_name,
                'username' => $employee->nigy,
                'password' => $password,
                'work_unit_code' => $employee->workUnit->code ?? '?',
            ];
        }

        return ['created' => $created, 'skipped' => $employees->count() - count($created)];
    }
}
