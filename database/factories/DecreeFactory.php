<?php

namespace Database\Factories;

use App\Enums\DecreeStatus;
use App\Models\Decree;
use App\Models\DecreeType;
use App\Models\Employee;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Decree>
 */
class DecreeFactory extends Factory
{
    public function definition(): array
    {
        $workUnit = WorkUnit::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'decree_type_id' => DecreeType::factory(),
            'employee_id' => Employee::factory()->create(['work_unit_id' => $workUnit->id]),
            'work_unit_id' => $workUnit->id,
            'status' => DecreeStatus::Draft,
            'academic_year' => '2026/2027',
            'is_legacy' => false,
        ];
    }
}
