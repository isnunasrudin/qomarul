<?php

namespace Database\Factories;

use App\Enums\AdditionalDutyStatus;
use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\EmployeeAdditionalDuty;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAdditionalDuty>
 */
class EmployeeAdditionalDutyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'additional_duty_id' => AdditionalDuty::factory(),
            'work_unit_id' => WorkUnit::factory(),
            'academic_year' => '2026/2027',
            'start_date' => fake()->date('Y-m-d', '-30 days'),
            'end_date' => fake()->date('Y-m-d', '+300 days'),
            'status' => AdditionalDutyStatus::Active,
        ];
    }
}
