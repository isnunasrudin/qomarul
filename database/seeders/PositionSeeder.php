<?php

namespace Database\Seeders;

use App\Enums\PositionGroup;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['code' => 'GURU-KELAS', 'name' => 'Guru Kelas', 'group' => PositionGroup::Educator],
            ['code' => 'GURU-MAPEL', 'name' => 'Guru Mata Pelajaran', 'group' => PositionGroup::Educator],
            ['code' => 'KEPSEK', 'name' => 'Kepala Sekolah', 'group' => PositionGroup::Educator],
            ['code' => 'WAKIL-KEPSEK', 'name' => 'Wakil Kepala Sekolah', 'group' => PositionGroup::Educator],
            ['code' => 'TENAGA-ADM', 'name' => 'Tenaga Administrasi', 'group' => PositionGroup::EducationStaff],
            ['code' => 'PUSTAKAWAN', 'name' => 'Pustakawan', 'group' => PositionGroup::EducationStaff],
            ['code' => 'PENJAGA', 'name' => 'Penjaga', 'group' => PositionGroup::EducationStaff],
            ['code' => 'KEAMANAN', 'name' => 'Petugas Keamanan', 'group' => PositionGroup::EducationStaff],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['code' => $position['code']],
                ['name' => $position['name'], 'group' => $position['group']->value, 'is_active' => true],
            );
        }
    }
}
