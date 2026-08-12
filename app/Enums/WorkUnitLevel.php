<?php

namespace App\Enums;

enum WorkUnitLevel: string
{
    case Tpq = 'TPQ';
    case Sd = 'SD';
    case Smp = 'SMP';
    case Sma = 'SMA';
    case Smk = 'SMK';
    case IslamicBoardingSchool = 'PONDOK';
    case FoundationOffice = 'KANTOR_YAYASAN';
    case Others = 'LAINNYA';

    public function label(): string
    {
        return __('enums.work_unit_level.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
