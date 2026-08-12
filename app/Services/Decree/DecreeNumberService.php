<?php

namespace App\Services\Decree;

use App\Models\Decree;
use App\Services\Numbering\NumberAllocator;
use App\Support\RomanMonth;
use Illuminate\Support\Carbon;

/**
 * Nomor SK (PRD §5.5.2): seri per jenis × satker × tahun kalender,
 * nomor registrasi global E-xxxx, reset 1 Januari.
 */
class DecreeNumberService
{
    public function __construct(private readonly NumberAllocator $allocator) {}

    /**
     * Alokasikan nomor urut & registrasi saat SK diverifikasi.
     *
     * @return array{decree_number: string, sequence_number: int, registration_number: string, allocated_at: Carbon}
     */
    public function allocate(Decree $decree): array
    {
        $type = $decree->decreeType;
        $workUnit = $decree->workUnit;
        $year = (int) Carbon::parse($decree->issued_date)->year;

        $sequence = $this->allocator->allocate("decree:{$type->code}:{$workUnit->code}:{$year}", $year);

        // seri registrasi global lintas seluruh SK (PRD F5.15) — kunci tetap
        $registration = $this->allocator->allocate('decree:registration', 0);

        $number = $this->render(
            format: $type->number_format ?: '{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}',
            padding: (int) $type->number_padding ?: 3,
            sequence: $sequence,
            decreeTypeCode: $type->code,
            workUnitCode: $workUnit->code,
            issuedDate: $decree->issued_date,
            academicYear: $decree->academic_year,
        );

        return [
            'decree_number' => $number,
            'sequence_number' => $sequence,
            'registration_number' => "E-{$registration}",
            'allocated_at' => now(),
        ];
    }

    /**
     * Render format nomor dari token.
     */
    public function render(
        string $format,
        int $padding,
        int $sequence,
        string $decreeTypeCode,
        string $workUnitCode,
        Carbon|string|null $issuedDate,
        ?string $academicYear,
    ): string {
        $date = Carbon::parse($issuedDate ?? now());

        return str_replace(
            [
                '{nomor}', '{kode_jenis}', '{kode_satker}', '{bulan_romawi}', '{bulan}',
                '{tahun}', '{tahun_pelajaran}',
            ],
            [
                str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
                $decreeTypeCode,
                $workUnitCode,
                RomanMonth::fromMonth($date->month),
                $date->format('m'),
                (string) $date->year,
                $academicYear ?: '',
            ],
            $format,
        );
    }
}
