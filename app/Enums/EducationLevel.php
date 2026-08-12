<?php

namespace App\Enums;

enum EducationLevel: string
{
    case Sd = 'SD';
    case Smp = 'SMP';
    case Sma = 'SMA';
    case Smk = 'SMK';
    case D1 = 'D1';
    case D2 = 'D2';
    case D3 = 'D3';
    case D4 = 'D4';
    case S1 = 'S1';
    case S2 = 'S2';
    case S3 = 'S3';

    public function label(): string
    {
        return __('enums.education_level.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
