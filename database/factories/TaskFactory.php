<?php

namespace Database\Factories;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
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
            'group_id' => 0,
            'name' => fake()->sentence(1),
            'description' => fake()->sentence(40),
            'type' => Arr::random([Task::TYPE_DISCOURAGE, Task::TYPE_ENCOURAGE]),
            'value' => rand(1, 99),
            'count' => 0,
            'target_count' => $count = rand(1,9),
            'max_count' => rand($count, 9),
            'rrule' => 'RRULE:FREQ=DAILY;INTERVAL=1',
            'start' => Carbon::now()->format('Y-m-d'),
            'end' => rand(0, 1) == 1 ? null : Carbon::now()->addDays(rand(1,99))->format('Y-m-d'),
            'remove_points_on_failure' => rand(0, 1),
            'remind' => $remind = rand(0, 1),
            'remind_time' => $remind == 1 ? Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s') : null,
            'restrict' => $restrict = rand(0, 1),
            'restrict_time' => $restrict == 1 ? Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s') : null,
            'restrict_before' => rand(0, 1),
        ];
    }
}
