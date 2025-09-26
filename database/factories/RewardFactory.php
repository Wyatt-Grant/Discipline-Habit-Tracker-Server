<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reward>
 */
class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dynamic_id' => 1,
            'name' => fake()->sentence(1),
            'description' => fake()->sentence(50),
            'value' => rand(1,99),
            'bank' => rand(1,99),
        ];
    }
}
