<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\User;

/**
 * Sinkronisasi status akun portal dengan status kepegawaian (PRD F1.2b):
 * GTK dinonaktifkan (pensiun, mutasi keluar, wafat) → akun ikut nonaktif.
 */
class EmployeeObserver
{
    public function updated(Employee $employee): void
    {
        if (! $employee->wasChanged('is_active')) {
            return;
        }

        User::query()
            ->where('employee_id', $employee->id)
            ->update(['is_active' => $employee->is_active]);
    }
}
