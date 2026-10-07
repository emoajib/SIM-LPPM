<?php

namespace Database\Factories;

use App\Enums\BudgetAmendmentStatus;
use App\Models\BudgetAmendment;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetAmendment>
 */
class BudgetAmendmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'version' => 2,
            'status' => BudgetAmendmentStatus::PENDING,
            'reason' => fake()->sentence(),
        ];
    }
}
