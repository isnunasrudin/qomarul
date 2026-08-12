<?php

namespace App\Services\Employee;

use App\Models\Employee;

/**
 * Indikator kelengkapan profil GTK (PRD F3.6): persentase + daftar
 * field/berkas yang kurang.
 */
class ProfileCompletenessService
{
    /** @var array<string, array<int, string>> */
    private array $checklist = [
        'pribadi' => [
            'nik', 'birth_place', 'birth_date', 'religion', 'marital_status',
            'mother_name', 'address', 'phone', 'email', 'photo_path',
        ],
        'kepegawaian' => [
            'position_id', 'employment_status_id', 'foundation_start_date',
            'unit_start_date', 'subject',
        ],
    ];

    /**
     * @return array{percentage: int, missing: array<int, string>, complete: bool}
     */
    public function evaluate(Employee $employee): array
    {
        $missing = [];

        foreach ($this->checklist as $group => $fields) {
            foreach ($fields as $field) {
                if (blank($employee->{$field})) {
                    $missing[] = $group.'.'.$field;
                }
            }
        }

        if (! $employee->educations()->where('is_highest', true)->exists()) {
            $missing[] = 'pendidikan.tertinggi';
        }

        foreach (['ktp', 'diploma'] as $category) {
            if (! $employee->documents()->where('category', $category)->exists()) {
                $missing[] = 'berkas.'.$category;
            }
        }

        $total = $this->totalItems();
        $completed = $total - count($missing);
        $percentage = $total > 0 ? (int) round($completed / $total * 100) : 0;

        return [
            'percentage' => $percentage,
            'missing' => $missing,
            'complete' => $percentage === 100,
        ];
    }

    protected function totalItems(): int
    {
        return array_sum(array_map('count', $this->checklist)) + 1 + 2;
    }
}
