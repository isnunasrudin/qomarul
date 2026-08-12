<?php

namespace Database\Factories;

use App\Enums\DecreeBatchStatus;
use App\Models\DecreeBatch;
use App\Models\DecreeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecreeBatch>
 */
class DecreeBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'decree_type_id' => DecreeType::factory(),
            'academic_year' => '2026/2027',
            'effective_date' => fake()->date(),
            'issued_date' => fake()->date(),
            'total' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'status' => DecreeBatchStatus::Preparing,
        ];
    }
}
