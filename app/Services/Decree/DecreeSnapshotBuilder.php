<?php

namespace App\Services\Decree;

use App\Models\Decree;
use App\Models\Setting;
use App\Models\User;
use App\Support\IndonesianDate;
use App\Support\ServicePeriod;

/**
 * Bekukan seluruh nilai tercetak ke snapshot_data saat SK diterbitkan
 * (PRD F5.3): perubahan data GTK kemudian tidak mengubah SK terbit.
 */
class DecreeSnapshotBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Decree $decree): array
    {
        $employee = $decree->employee->load(['workUnit', 'position', 'employmentStatus']);
        $highest = $employee->educations()->where('is_highest', true)->first();

        $effectiveDate = $decree->effective_date;

        $period = ServicePeriod::between($employee->foundation_start_date, $effectiveDate);

        $consideration = $decree->decreeType;

        return [
            'name' => $employee->full_name,
            'title_prefix' => $employee->title_prefix,
            'name_plain' => $employee->name,
            'title_suffix' => $employee->title_suffix,
            'nigy' => $employee->nigy,
            'birth_place' => $employee->birth_place,
            'birth_date' => $employee->birth_date ? IndonesianDate::format($employee->birth_date) : null,
            'gender' => $employee->gender,
            'education_level' => $highest?->level?->value,
            'major' => $highest?->major,
            'position' => $decree->position_snapshot ?? $employee->position?->name,
            'appointed_as' => $decree->appointed_as,
            'work_unit' => $employee->workUnit?->name,
            'work_unit_code' => $employee->workUnit?->code,
            'foundation_start_date' => $employee->foundation_start_date ? IndonesianDate::format($employee->foundation_start_date) : null,
            'unit_start_date' => $employee->unit_start_date ? IndonesianDate::format($employee->unit_start_date) : null,
            'service_years' => $period->years,
            'service_months' => $period->months,
            'effective_date' => $effectiveDate ? IndonesianDate::format($effectiveDate) : null,
            'issued_place' => $decree->issued_place ?: Setting::get('foundation.default_issued_place', 'Gondang'),
            'issued_date' => $decree->issued_date ? IndonesianDate::format($decree->issued_date) : null,
            'chairman_name' => Setting::get('foundation.chairman_name', ''),
            'chairman_position' => Setting::get('foundation.chairman_position', 'Ketua Yayasan'),
            'consideration_recalling' => $consideration->consideration_recalling,
            'consideration_weighing' => $consideration->consideration_weighing ?? [],
            'consideration_observing' => $consideration->consideration_observing,
            'foundation' => [
                'name' => Setting::get('foundation.name', 'Yayasan Pondok Pesantren Qomarul Hidayah'),
                'address' => Setting::get('foundation.address', ''),
                'notary_deed' => Setting::get('foundation.notary_deed', ''),
                'sk_menkumham' => Setting::get('foundation.sk_menkumham', ''),
            ],
            'cc_list' => Setting::get('letterhead.cc_list', []),
            'academic_year' => $decree->academic_year,
            'decree_number' => $decree->decree_number,
            'registration_number' => $decree->registration_number,
            'decree_type' => $decree->decreeType->name,
            'decree_type_code' => $decree->decreeType->code,
        ];
    }

    /**
     * Simpan snapshot dan bekukan jabatan yang tercetak.
     */
    public function freeze(Decree $decree, ?User $actor = null): Decree
    {
        $data = $this->build($decree);

        $decree->update([
            'snapshot_data' => $data,
            'position_snapshot' => $data['position'],
        ]);

        return $decree->fresh();
    }
}
