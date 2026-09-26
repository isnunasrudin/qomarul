<?php

namespace App\Services\Nigy;

use App\Models\Decree;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Setting;
use App\Models\WorkUnit;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Aturan NIGY terpusat (PRD F3.2a–F3.2g).
 */
class NigyService
{
    /** Batas percobaan bila NIGY hasil generate ternyata sudah dipakai. */
    private const MAX_ATTEMPTS = 50;

    public function __construct(private readonly NigyGenerator $generator) {}

    /**
     * Bangkitkan NIGY otomatis untuk GTK baru.
     * Kunci penghitung: nigy:{kode_satker}:{tahun_masuk}.
     */
    public function generate(Employee $employee): string
    {
        return $this->allocate(
            $employee->workUnit,
            $employee->foundation_start_date,
            $employee->birth_date,
            (int) $employee->employment_status_id,
            $employee->employmentStatus?->code,
        );
    }

    /**
     * Bangkitkan NIGY dari data mentah form sebelum record dibuat
     * (kolom nigy NOT NULL, jadi harus tersedia saat insert).
     *
     * @param  array<string, mixed>  $data
     */
    public function generateFromData(array $data): string
    {
        $workUnit = WorkUnit::findOrFail($data['work_unit_id']);

        $foundationStartDate = isset($data['foundation_start_date']) ? Carbon::parse($data['foundation_start_date']) : null;
        $birthDate = ! empty($data['birth_date']) ? Carbon::parse($data['birth_date']) : null;
        $statusId = isset($data['employment_status_id']) ? (int) $data['employment_status_id'] : null;

        return $this->allocate(
            $workUnit,
            $foundationStartDate,
            $birthDate,
            $statusId,
            $statusId !== null ? EmploymentStatus::find($statusId)?->code : null,
        );
    }

    /**
     * Render NIGY lalu pastikan nomornya belum dipakai.
     *
     * Penghitung urut bisa tertinggal dari data yang sudah ada — misalnya NIGY
     * lama hasil migrasi atau impor yang diisi langsung tanpa lewat generator.
     * Bila hasil render ternyata sudah dipakai, nomor berikutnya diambil alih
     * daripada membiarkan galat unique muncul sebagai 500.
     */
    protected function allocate(
        WorkUnit $workUnit,
        ?Carbon $foundationStartDate,
        ?Carbon $birthDate,
        ?int $employmentStatusId,
        ?string $employmentStatusCode,
    ): string {
        $format = (string) Setting::get('nigy.format', '{tahun_masuk}{kode_satker}{urut}');
        $padding = (int) Setting::get('nigy.padding', 3);
        $year = $this->generator->yearOf($foundationStartDate);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $nigy = $this->generator->render(
                format: $format,
                padding: $padding,
                workUnitCode: $workUnit->code,
                workUnitLevel: $workUnit->level->value,
                foundationStartDate: $foundationStartDate,
                sequence: $this->generator->nextSequence($workUnit->code, $year),
                birthDate: $birthDate,
                employmentStatusId: $employmentStatusId,
                employmentStatusCode: $employmentStatusCode,
            );

            if (! Employee::where('nigy', $nigy)->exists()) {
                return $nigy;
            }
        }

        throw new RuntimeException(
            'Tidak dapat mengalokasikan NIGY unik setelah '.self::MAX_ATTEMPTS.' percobaan. '.
            'Periksa format NIGY di Pengaturan atau selaraskan penghitung nomornya.',
        );
    }

    /**
     * NIGY terkunci bila GTK sudah termuat pada SK berstatus issued
     * (F3.2f) — termasuk SK yang dibatalkan/diganti tetap memegang nomornya.
     */
    public function isLocked(Employee $employee): bool
    {
        return Decree::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['issued', 'cancelled', 'superseded'])
            ->exists();
    }

    /**
     * Nomor SK yang mengunci NIGY, untuk pesan pemblokiran (F3.2f).
     *
     * @return array<int, string>
     */
    public function lockingDecreeNumbers(Employee $employee): array
    {
        return Decree::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['issued', 'cancelled', 'superseded'])
            ->pluck('decree_number')
            ->filter()
            ->values()
            ->all();
    }
}
