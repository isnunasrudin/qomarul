<?php

namespace Database\Seeders;

use App\Models\EmploymentStatus;
use Illuminate\Database\Seeder;

class EmploymentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'TETAP', 'name' => 'Tetap Yayasan'],
            ['code' => 'KONTRAK', 'name' => 'Kontrak'],
            ['code' => 'HONORER', 'name' => 'Honorer'],
            ['code' => 'PNS-DPK', 'name' => 'PNS DPK'],
        ];

        foreach ($statuses as $status) {
            EmploymentStatus::updateOrCreate(
                ['code' => $status['code']],
                ['name' => $status['name'], 'is_active' => true],
            );
        }
    }
}
