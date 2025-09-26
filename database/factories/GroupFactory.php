<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    private static $sortOrder = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dynamic_id' => 1,
            'sort_order' => self::$sortOrder++,
            'name' => fake()->company(),
            'color' => substr(fake()->hexColor, -6),
        ];
    }
}
