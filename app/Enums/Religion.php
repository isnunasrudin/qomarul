<?php

namespace App\Enums;

enum Religion: string
{
    case Islam = 'islam';
    case Kristen = 'kristen';
    case Katolik = 'katolik';
    case Hindu = 'hindu';
    case Buddha = 'buddha';
    case Konghucu = 'konghucu';

    public function label(): string
    {
        return __('enums.religion.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
