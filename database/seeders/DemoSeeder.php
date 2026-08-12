<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\WorkUnitLevel;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo — HANYA dijalankan saat APP_ENV=local.
 * Memuat satuan kerja contoh, seorang GTK contoh, dan satu akun per peran
 * agar definisi of done F1 ("empat peran dapat login") dapat dibuktikan.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $sd = WorkUnit::updateOrCreate(['code' => 'SD1'], [
            'name' => 'SD Qomarul Hidayah 1',
            'level' => WorkUnitLevel::Sd->value,
            'head_name' => 'Drs. Imam Syafi\'i',
            'is_active' => true,
        ]);

        $smp = WorkUnit::updateOrCreate(['code' => 'SMP'], [
            'name' => 'SMP Qomarul Hidayah',
            'level' => WorkUnitLevel::Smp->value,
            'is_active' => true,
        ]);

        $smk = WorkUnit::updateOrCreate(['code' => 'SMK'], [
            'name' => 'SMK Qomarul Hidayah',
            'level' => WorkUnitLevel::Smk->value,
            'is_active' => true,
        ]);

        $employee = Employee::updateOrCreate(['nigy' => '2026SD1001'], [
            'nik' => '3503031234567890',
            'name' => 'Ahmad Fauzi, S.Pd.',
            'gender' => 'male',
            'birth_place' => 'Trenggalek',
            'birth_date' => '1990-04-15',
            'religion' => 'islam',
            'marital_status' => 'married',
            'address' => 'Dsn. Gondang, Kec. Tugu',
            'village' => 'Gondang',
            'district' => 'Tugu',
            'regency' => 'Trenggalek',
            'province' => 'Jawa Timur',
            'phone' => '081234567890',
            'work_unit_id' => $sd->id,
            'position_id' => 1,
            'employment_status_id' => 1,
            'foundation_start_date' => '2020-07-01',
            'unit_start_date' => '2020-07-01',
            'is_active' => true,
        ]);

        $users = [
            [
                'name' => 'Ketua Yayasan',
                'username' => 'ketua',
                'email' => 'ketua@qomarulhidayah.sch.id',
                'role' => UserRole::FoundationHead,
                'must_change_password' => true,
            ],
            [
                'name' => 'Admin Yayasan',
                'username' => 'admin',
                'email' => 'admin@qomarulhidayah.sch.id',
                'role' => UserRole::FoundationAdmin,
                'must_change_password' => true,
            ],
            [
                'name' => 'Admin SD1',
                'username' => 'admin.sd1',
                'email' => 'admin.sd1@qomarulhidayah.sch.id',
                'role' => UserRole::UnitAdmin,
                'work_unit_id' => $sd->id,
                'must_change_password' => true,
            ],
            [
                'name' => 'Ahmad Fauzi',
                'username' => $employee->nigy,
                'email' => 'ahmad.fauzi@qomarulhidayah.sch.id',
                'role' => UserRole::Employee,
                'employee_id' => $employee->id,
                'work_unit_id' => $sd->id,
                'must_change_password' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']],
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make('Qomarul123!'),
                    'role' => $user['role']->value,
                    'work_unit_id' => $user['work_unit_id'] ?? null,
                    'employee_id' => $user['employee_id'] ?? null,
                    'is_active' => true,
                    'must_change_password' => $user['must_change_password'],
                ],
            );
        }
    }
}
