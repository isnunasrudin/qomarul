<?php

namespace Database\Factories;

use App\Enums\WorkUnitLevel;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkUnit>
 */
class WorkUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{2,4}[0-9]{0,2}'),
            'name' => fake()->company(),
            'level' => fake()->randomElement(WorkUnitLevel::cases())->value,
            'is_active' => true,
        ];
    }
}
