<?php

namespace App\Support;

class RomanMonth
{
    private const ROMAN = [
        1 => 'I', 'II', 'III', 'IV', 'V', 'VI',
        'VII', 'VIII', 'IX', 'X', 'XI', 'XII',
    ];

    public static function fromMonth(int $month): string
    {
        return self::ROMAN[$month] ?? '';
    }
}
