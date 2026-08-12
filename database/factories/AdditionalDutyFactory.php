<?php

namespace Database\Factories;

use App\Models\AdditionalDuty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdditionalDuty>
 */
class AdditionalDutyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{3,6}-[A-Z]{2,4}'),
            'name' => fake()->unique()->words(3, true),
            'requires_decree' => fake()->boolean(),
            'is_active' => true,
        ];
    }
}
