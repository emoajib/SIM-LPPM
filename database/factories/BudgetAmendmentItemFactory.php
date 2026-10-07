<?php

namespace Database\Factories;

use App\Models\BudgetAmendment;
use App\Models\BudgetAmendmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetAmendmentItem>
 */
class BudgetAmendmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $volume = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 50000, 1000000);

        return [
            'budget_amendment_id' => BudgetAmendment::factory(),
            'year' => 1,
            'group' => fake()->word(),
            'component' => fake()->word(),
            'item_description' => fake()->sentence(),
            'volume' => $volume,
            'unit_price' => $unitPrice,
            'total_price' => $volume * $unitPrice,
        ];
    }
}
