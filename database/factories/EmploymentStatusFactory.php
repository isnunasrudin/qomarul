<?php

namespace Database\Factories;

use App\Models\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentStatus>
 */
class EmploymentStatusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{4,8}'),
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
