<?php

namespace App\Services\Duty;

use App\Models\EmployeeAdditionalDuty;
use Illuminate\Support\Carbon;

/**
 * Validasi irisan periode penetapan tugas tambahan (PRD F4.3):
 * satu GTK tidak boleh memegang referensi tugas yang sama pada rentang
 * tanggal yang beririsan.
 */
class DutyOverlapService
{
    /**
     * Apakah rentang [start, end] beririsan dengan rentang lain
     * [otherStart, otherEnd]? Dua rentang beririsan bila
     * start <= otherEnd DAN end >= otherStart. Bersinggungan tepat di
     * ujung tanggal (mis. selesai 30/6, mulai baru 1/7) TIDAK dihitung irisan.
     */
    public function rangesOverlap(
        Carbon $start,
        Carbon $end,
        Carbon $otherStart,
        Carbon $otherEnd,
    ): bool {
        return $start->lte($otherEnd) && $end->gte($otherStart);
    }

    /**
     * Periksa seluruh penetapan lain GTK yang memakai referensi yang sama.
     */
    public function findOverlapping(
        int $employeeId,
        int $additionalDutyId,
        Carbon $start,
        Carbon $end,
        ?int $ignoreAssignmentId = null,
    ): ?EmployeeAdditionalDuty {
        $query = EmployeeAdditionalDuty::query()
            ->where('employee_id', $employeeId)
            ->where('additional_duty_id', $additionalDutyId)
            ->where('status', '!=', 'cancelled');

        if ($ignoreAssignmentId) {
            $query->where('id', '!=', $ignoreAssignmentId);
        }

        return $query->get()
            ->first(fn (EmployeeAdditionalDuty $existing) => $this->rangesOverlap(
                $start,
                $end,
                Carbon::parse($existing->start_date),
                Carbon::parse($existing->end_date),
            ));
    }
}
