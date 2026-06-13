<?php

namespace Database\Factories;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterFactory extends Factory
{
    protected $model = Letter::class;

    public function definition(): array
    {
        return [
            'letter_type_id' => LetterType::factory(),
            'user_id' => User::factory(),
            'reference_type' => 'App\Models\Proposal',
            'reference_id' => $this->faker->uuid(),
            'signature_mode' => 'tte',
            'status' => 'pending_approval',
            'metadata' => [],
            'team_snapshot' => [],
        ];
    }
}
