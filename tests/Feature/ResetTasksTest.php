<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Punishment;
use App\Models\PunishmentHistory;
use App\Models\Reward;
use App\Models\RewardHistory;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_resets_tasks_in_25_minute_window_after_midnight(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $count = rand(1,10);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'count' => $count,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
            'start' => '2024-02-01',
            'end' => null,
        ])->each->refresh();

        $tasks->each(function ($task) use ($count) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'count' => $count,
            ]);
        });

        //assert does not run before midnight
        Carbon::setTestNow(Carbon::parse('2024-02-04 23:59:59', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();
        $tasks->each(function ($task) use ($count) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'count' => $count,
            ]);
        });

        //assert does not run after 25 mins after midnight
        Carbon::setTestNow(Carbon::parse('2024-02-05 00:25:01', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();
        $tasks->each(function ($task) use ($count) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'count' => $count,
            ]);
        });

        //assert runs at midnight
        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) use ($count) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'count' => 0,
            ]);
        });
    }

    public function test_resets_tasks_generates_history(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'start' => '2024-02-01',
            'end' => null,
        ])->each->refresh();

        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) {
            $this->assertDatabaseHas('task_histories', [
                'task_id' => $task->id,
                'date' => '2024-02-04',
            ]);
        });
    }

    public function test_resets_tasks_only_resets_tasks_due_today(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $count = rand(1,10);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'start' => '2024-02-07',
            'end' => '2024-02-08',
            'count' => $count,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
        ])->each->refresh();

        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) use ($count) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'count' => $count,
            ]);
        });
    }

    public function test_resets_tasks_generates_complete_history_for_encourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $count = rand(1,10);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'count' => $count,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
            'start' => '2024-02-01',
            'end' => null,
        ])->each->refresh();

        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) {
            $this->assertDatabaseHas('task_histories', [
                'task_id' => $task->id,
                'date' => '2024-02-04',
                'was_complete' => 1,
            ]);
        });
    }

    public function test_resets_tasks_generates_complete_history_for_discourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $count = rand(1,10);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 0,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
            'start' => '2024-02-01',
            'end' => null,
        ])->each->refresh();

        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) {
            $this->assertDatabaseHas('task_histories', [
                'task_id' => $task->id,
                'date' => '2024-02-04',
                'was_complete' => 1,
            ]);
        });
    }

    public function test_resets_tasks_generates_incomplete_history_for_encourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $count = rand(1,10);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'count' => 0,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
            'start' => '2024-02-01',
            'end' => null,
        ])->each->refresh();

        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) {
            $this->assertDatabaseHas('task_histories', [
                'task_id' => $task->id,
                'date' => '2024-02-04',
                'was_complete' => 0,
            ]);
        });
    }

    public function test_resets_tasks_generates_incomplete_history_for_discourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic->users()->attach($user->id);
        $count = rand(1,10);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'count' => $count,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
            'start' => '2024-02-01',
            'end' => null,
        ])->each->refresh();

        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $this->artisan('app:reset-tasks')->assertSuccessful();

        $tasks->each(function ($task) {
            $this->assertDatabaseHas('task_histories', [
                'task_id' => $task->id,
                'date' => '2024-02-04',
                'was_complete' => 0,
            ]);
        });
    }

    public function test_resets_tasks_gives_punishments_for_encourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
        ])->refresh();
        $punishments = Punishment::factory()->count(5)->create([
            'value' => 0,
            'dynamic_id' => $dynamic->id,
        ]);
        $punishments->each(fn($punishment) => $punishment->tasks()->attach($task));

        $this->artisan('app:reset-tasks')->assertSuccessful();

        $punishments->each(function ($punishment) use ($task) {
            $this->assertDatabaseHas('punishment_histories', [
                'punishment_id' => $punishment->id,
                'date' => '2024-02-05',
                'action' => PunishmentHistory::AUTO_ASSIGNED,
            ]);
            $this->assertDatabaseHas('punishments', [
                'id' => $punishment->id,
                'value' => 1,
            ]);
        });
    }

    public function test_resets_tasks_gives_punishments_for_discourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $count = rand(1,10);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'count' => $count,
            'target_count' => $count,
            'max_count' => rand($count, $count + 10),
        ])->refresh();
        $punishments = Punishment::factory()->count(5)->create([
            'value' => 0,
            'dynamic_id' => $dynamic->id,
        ]);
        $punishments->each(fn($punishment) => $punishment->tasks()->attach($task));

        $this->artisan('app:reset-tasks')->assertSuccessful();

        $punishments->each(function ($punishment) use ($task) {
            $this->assertDatabaseHas('punishment_histories', [
                'punishment_id' => $punishment->id,
                'date' => '2024-02-05',
                'action' => PunishmentHistory::AUTO_ASSIGNED,
            ]);
            $this->assertDatabaseHas('punishments', [
                'id' => $punishment->id,
                'value' => 1,
            ]);
        });
    }

    public function test_resets_tasks_gives_rewards_for_discourage_tasks(): void
    {
        $dynamic = Dynamic::factory()->create()->refresh();
        Carbon::setTestNow(Carbon::parse('2024-02-05 00:00:00', $dynamic->time_zone));
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
        ])->refresh();
        $rewards = Reward::factory()->count(5)->create([
            'bank' => 0,
            'dynamic_id' => $dynamic->id,
        ]);
        $rewards->each(fn($reward) => $reward->tasks()->attach($task));

        $this->artisan('app:reset-tasks')->assertSuccessful();

        $rewards->each(function ($reward) use ($task) {
            $this->assertDatabaseHas('reward_histories', [
                'reward_id' => $reward->id,
                'date' => '2024-02-05',
                'action' => RewardHistory::AUTO_GIVEN,
            ]);
            $this->assertDatabaseHas('rewards', [
                'id' => $reward->id,
                'bank' => 1,
            ]);
        });
    }
}
