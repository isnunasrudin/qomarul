<?php

namespace App\Services\Nigy;

use App\Models\Decree;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\WorkUnit;
use Illuminate\Support\Carbon;

/**
 * Aturan NIGY terpusat (PRD F3.2a–F3.2g).
 */
class NigyService
{
    public function __construct(private readonly NigyGenerator $generator) {}

    /**
     * Bangkitkan NIGY otomatis untuk GTK baru.
     * Kunci penghitung: nigy:{kode_satker}:{tahun_masuk}.
     */
    public function generate(Employee $employee): string
    {
        $workUnit = $employee->workUnit;

        $sequence = $this->generator->nextSequence($workUnit->code, (int) $employee->foundation_start_date?->year);

        return $this->generator->render(
            format: (string) Setting::get('nigy.format', '{tahun_masuk}{kode_satker}{urut}'),
            padding: (int) Setting::get('nigy.padding', 3),
            workUnitCode: $workUnit->code,
            workUnitLevel: $workUnit->level->value,
            foundationStartDate: $employee->foundation_start_date,
            sequence: $sequence,
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

        $sequence = $this->generator->nextSequence($workUnit->code, (int) $foundationStartDate?->year);

        return $this->generator->render(
            format: (string) Setting::get('nigy.format', '{tahun_masuk}{kode_satker}{urut}'),
            padding: (int) Setting::get('nigy.padding', 3),
            workUnitCode: $workUnit->code,
            workUnitLevel: $workUnit->level->value,
            foundationStartDate: $foundationStartDate,
            sequence: $sequence,
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
