<?php

namespace Database\Factories;

use App\Enums\ResearchSchemeStrata;
use App\Models\ResearchScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchScheme>
 */
class ResearchSchemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Penelitian Dasar',
                'Penelitian Terapan',
                'Penelitian Pengembangan',
                'Penelitian Dosen Pemula',
                'Penelitian Kompetitif Nasional',
            ]),
            'strata' => fake()->randomElement(ResearchSchemeStrata::values()),
            'description' => fake()->sentence(),
        ];
    }
}
