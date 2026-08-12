<?php

namespace App\Enums;

enum DecreeBatchStatus: string
{
    case Preparing = 'preparing';
    case Processing = 'processing';
    case AwaitingSignature = 'awaiting_signature';
    case Signing = 'signing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.decree_batch_status.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
