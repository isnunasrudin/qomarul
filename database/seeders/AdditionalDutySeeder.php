<?php

namespace Database\Seeders;

use App\Models\AdditionalDuty;
use Illuminate\Database\Seeder;

class AdditionalDutySeeder extends Seeder
{
    public function run(): void
    {
        $duties = [
            ['code' => 'WALI-KELAS', 'name' => 'Wali Kelas', 'requires_decree' => true],
            ['code' => 'KAPUS', 'name' => 'Kepala Perpustakaan', 'requires_decree' => true],
            ['code' => 'KALAB', 'name' => 'Kepala Laboratorium', 'requires_decree' => true],
            ['code' => 'PEMBINA-OSIS', 'name' => 'Pembina OSIS', 'requires_decree' => true],
            ['code' => 'BENDAHARA', 'name' => 'Bendahara', 'requires_decree' => true],
            ['code' => 'OPS-DAPODIK', 'name' => 'Operator Dapodik', 'requires_decree' => true],
            ['code' => 'WAKA-KURIKULUM', 'name' => 'Wakil Kepala Bidang Kurikulum', 'requires_decree' => true],
            ['code' => 'WAKA-KESISWAAN', 'name' => 'Wakil Kepala Bidang Kesiswaan', 'requires_decree' => true],
            ['code' => 'WAKA-SARPRAS', 'name' => 'Wakil Kepala Bidang Sarana Prasarana', 'requires_decree' => true],
            ['code' => 'WAKA-HUMAS', 'name' => 'Wakil Kepala Bidang Humas', 'requires_decree' => true],
            ['code' => 'WALI-KELAS-PONPES', 'name' => 'Wali Asuh Santri', 'requires_decree' => false],
        ];

        foreach ($duties as $duty) {
            AdditionalDuty::updateOrCreate(
                ['code' => $duty['code']],
                [
                    'name' => $duty['name'],
                    'requires_decree' => $duty['requires_decree'],
                    'is_active' => true,
                ],
            );
        }
    }
}
