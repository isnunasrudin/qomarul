<?php

namespace App\Enums;

enum DecreeStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Verified = 'verified';
    case Issued = 'issued';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public function label(): string
    {
        return __('enums.decree_status.'.$this->value);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Issued, self::Cancelled, self::Superseded], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
