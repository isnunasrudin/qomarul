<?php

namespace Database\Factories;

use App\Models\DecreeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DecreeType>
 */
class DecreeTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->regexify('SK-[A-Z]{3}'),
            'name' => fake()->unique()->words(2, true),
            'number_format' => '{nomor}/{kode_jenis}/{kode_satker}/YPP-QH/{bulan_romawi}/{tahun}',
            'number_padding' => 3,
            'is_active' => true,
        ];
    }
}
