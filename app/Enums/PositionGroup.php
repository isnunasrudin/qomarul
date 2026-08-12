<?php

namespace App\Enums;

enum PositionGroup: string
{
    case Educator = 'educator';
    case EducationStaff = 'education_staff';

    public function label(): string
    {
        return __('enums.position_group.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
