<?php

namespace App\Services\Nigy;

use App\Services\Numbering\NumberAllocator;
use Illuminate\Support\Carbon;

/**
 * Pembangkit NIGY sesuai format yang dikonfigurasi di settings.
 *
 * Format default: {tahun_masuk}{kode_satker}{urut} → 2026SMK001.
 * Token: {tahun_masuk}, {bulan_masuk}, {kode_satker}, {kode_jenjang}, {urut}.
 * Nomor urut direset per tahun per satuan kerja lewat NumberAllocator.
 */
class NigyGenerator
{
    public function __construct(private readonly NumberAllocator $allocator) {}

    /**
     * Alokasikan nomor urut berikutnya untuk satuan kerja pada tahun masuk.
     */
    public function nextSequence(string $workUnitCode, int $year): int
    {
        return $this->allocator->allocate("nigy:{$workUnitCode}:{$year}", $year);
    }

    /**
     * Render NIGY dari format yang dikonfigurasi.
     */
    public function render(
        string $format,
        int $padding,
        string $workUnitCode,
        string $workUnitLevel,
        ?Carbon $foundationStartDate,
        int $sequence,
    ): string {
        $year = $foundationStartDate ? $foundationStartDate->year : (int) now()->year;
        $month = $foundationStartDate ? $foundationStartDate->format('m') : now()->format('m');

        return str_replace(
            ['{tahun_masuk}', '{bulan_masuk}', '{kode_satker}', '{kode_jenjang}', '{urut}'],
            [(string) $year, $month, $workUnitCode, $workUnitLevel, str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT)],
            $format,
        );
    }
}
