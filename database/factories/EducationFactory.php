<?php

namespace Database\Factories;

use App\Models\Education;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'level' => 'S1',
            'institution' => fake()->company(),
            'major' => fake()->words(2, true),
            'start_year' => fake()->year(),
            'end_year' => fake()->year(),
            'is_highest' => true,
        ];
    }
}
