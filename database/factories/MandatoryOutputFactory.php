<?php

namespace Database\Factories;

use App\Models\MandatoryOutput;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MandatoryOutput>
 */
class MandatoryOutputFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_title' => $this->faker->sentence(),
            'article_title' => $this->faker->sentence(),
            'status_type' => 'published',
            // author_status is NOT NULL in PostgreSQL (migration does not alter this for pgsql).
            // Always provide a default value to avoid constraint violations.
            // Vetted by AI - Manual Review Required by Senior Engineer/Manager
            'author_status' => 'first_author',
            'publication_year' => date('Y'),
        ];
    }
}
