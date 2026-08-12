<?php

namespace Database\Factories;

use App\Enums\PositionGroup;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('[A-Z]{4}-[A-Z]{3}'),
            'name' => fake()->jobTitle(),
            'group' => fake()->randomElement(PositionGroup::cases())->value,
            'is_active' => true,
        ];
    }
}
