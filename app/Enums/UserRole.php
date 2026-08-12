<?php

namespace App\Enums;

enum UserRole: string
{
    case FoundationHead = 'foundation_head';
    case FoundationAdmin = 'foundation_admin';
    case UnitAdmin = 'unit_admin';
    case Employee = 'employee';

    public function label(): string
    {
        return __('enums.role.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
