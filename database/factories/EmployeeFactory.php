<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Position;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nigy' => fake()->unique()->numerify('#######'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases())->value,
            'birth_place' => fake()->city(),
            'birth_date' => fake()->date('Y-m-d', '2005-01-01'),
            'religion' => 'islam',
            'marital_status' => 'married',
            'address' => fake()->address(),
            'phone' => fake()->numerify('08##########'),
            'work_unit_id' => WorkUnit::factory(),
            'position_id' => Position::factory(),
            'employment_status_id' => EmploymentStatus::factory(),
            'foundation_start_date' => fake()->date('Y-m-d', '-1 year'),
            'unit_start_date' => fake()->date('Y-m-d', '-1 year'),
            'is_active' => true,
        ];
    }
}
