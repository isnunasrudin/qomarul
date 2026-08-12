<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Ktp = 'ktp';
    case FamilyCard = 'family_card';
    case Diploma = 'diploma';
    case Transcript = 'transcript';
    case EducatorCertificate = 'educator_certificate';
    case TrainingCertificate = 'training_certificate';
    case OldDecree = 'old_decree';
    case PassportPhoto = 'passport_photo';
    case Npwp = 'npwp';
    case BankBook = 'bank_book';
    case Other = 'other';

    public function label(): string
    {
        return __('enums.document_category.'.$this->value);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
