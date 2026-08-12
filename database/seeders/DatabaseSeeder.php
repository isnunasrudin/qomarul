<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            PositionSeeder::class,
            EmploymentStatusSeeder::class,
            AdditionalDutySeeder::class,
            DecreeTypeSeeder::class,
            WorkUnitSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
