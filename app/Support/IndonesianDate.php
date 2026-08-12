<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Format tanggal ala Indonesia: "12 Agustus 2026".
 */
class IndonesianDate
{
    private const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public static function format(Carbon|string|null $date): string
    {
        if (! $date) {
            return '';
        }

        $date = Carbon::parse($date);

        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }
}
