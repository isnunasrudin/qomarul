<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Perhitungan masa kerja dalam tahun & bulan dari tanggal mulai ke tanggal acuan.
 */
class ServicePeriod
{
    public function __construct(
        public readonly int $years,
        public readonly int $months,
    ) {}

    public static function between(Carbon|string|null $start, Carbon|string|null $reference = null): self
    {
        if (! $start) {
            return new self(0, 0);
        }

        $start = Carbon::parse($start);
        $reference = Carbon::parse($reference ?? now());

        if ($start->isAfter($reference)) {
            return new self(0, 0);
        }

        $diff = $start->diff($reference);

        return new self($diff->y, $diff->m);
    }

    public function label(): string
    {
        return "{$this->years} tahun {$this->months} bulan";
    }
}
