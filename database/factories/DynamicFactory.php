<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dynamic>
 */
class DynamicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'time_zone' => fake()->timezone(),
            'default_reward_emojis' => fake()->emoji . fake()->emoji . fake()->emoji,
            'UUID' => Str::uuid(),
        ];
    }
}
