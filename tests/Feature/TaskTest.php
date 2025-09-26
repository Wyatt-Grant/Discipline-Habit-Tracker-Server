<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Group;
use App\Models\Message;
use App\Models\Reward;
use App\Models\RewardHistory;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_tasks_when_none(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks');

        $response->assertOk()->assertJson(["tasks" => []]);
    }

    public function test_can_get_all_tasks(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks');

        $response->assertOk();
        $tasks->each(fn($task) => $response->assertJsonFragment($task->toArray()));
    }

    public function test_can_get_all_tasks_with_groups(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $groups = Group::factory()->count(5)->create();
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id
        ]);
        $groups->each(fn ($group, $i) => $tasks[$i]->update([
            'group_id' => $group->id
        ]));
        $tasks->load('group')->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks');

        $response->assertOk();
        $tasks->each(fn($task) => $response->assertJsonFragment([
            'group_id' => $task->group?->id ?? 0,
            'color' => $task->group?->color
        ]));
    }

    public function test_can_get_all_tasks_when_some_not_due_today(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create([]);
        $dynamic->users()->attach($user->id);
        $tasksDue = Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh();
        $tasksNotDueBefore = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->subDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->subDays(1)->format('Y-m-d'),
        ])->each->refresh();
        $tasksNotDueAfter = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->addDays(1)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->addDays(2)->format('Y-m-d'),
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks');

        $response->assertOk();
        $tasksDue->each(fn() => $response->assertJsonFragment(['is_task_due_today' => 1]));
        $tasksNotDueBefore->each(fn() => $response->assertJsonFragment(['is_task_due_today' => 0]));
        $tasksNotDueAfter->each(fn() => $response->assertJsonFragment(['is_task_due_today' => 0]));
    }

    public function test_can_get_all_tasks_when_none_due_today(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create([]);
        $dynamic->users()->attach($user->id);
        $tasksNotDueBefore = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->subDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->subDays(1)->format('Y-m-d'),
        ])->each->refresh();
        $tasksNotDueAfter = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->addDays(1)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->addDays(2)->format('Y-m-d'),
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks');

        $response->assertOk();
        $tasksNotDueBefore->merge($tasksNotDueAfter)
            ->each(fn() => $response->assertJsonFragment(['is_task_due_today' => 0]));
    }

    public function test_can_create_task():void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'name' => 'test task',
            'description' => 'test desc',
            'type' => Task::TYPE_ENCOURAGE,
            'value' => 1,
            'count' => 1,
            'target_count' => 2,
            'max_count' => 2,
            'rrule' => 'RRULE:FREQ=DAILY;INTERVAL=1',
            'start' => Carbon::now()->format('Y-m-d'),
            'end' =>Carbon::now()->format('Y-m-d'),
            'remove_points_on_failure' => 1,
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(14, 23, 45),
            'restrict' => 1,
            'restrict_before' => 1,
            'restrict_time' => Carbon::createFromTime(14, 23, 45),
        ]);

        $response->assertJson(['message' => 'success']);
    }

    public function test_create_returns_errors_with_invalid_info():void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', []);

        $response->assertJson(['message' => 'The name field is required. The description field is required. The type field is required. The value field is required. The target count field is required. The max count field is required. The rrule field is required. The start field is required. The remove points on failure field is required. The remind field is required. The restrict field is required. The restrict before field is required.']);
    }

    public function test_can_update_task():void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $group = Group::factory()->create([
            'dynamic_id' => $dynamic->id,
        ]);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id
        ])->each->refresh();
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/task/{$tasks->last()->id}", [
            'name' => 'test task',
            'description' => 'test desc',
            'type' => Task::TYPE_ENCOURAGE,
            'value' => 1,
            'count' => 1,
            'target_count' => 2,
            'max_count' => 2,
            'rrule' => 'RRULE:FREQ=DAILY;INTERVAL=1',
            'start' => Carbon::now()->format('Y-m-d'),
            'end' =>Carbon::now()->format('Y-m-d'),
            'remove_points_on_failure' => 1,
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(14, 23, 45),
            'restrict' => 1,
            'restrict_before' => 1,
            'restrict_time' => Carbon::createFromTime(14, 23, 45),
            'group' => $group->id,
        ]);

        $response->assertJson(['message' => 'success']);
    }

    public function test_update_returns_errors_with_invalid_info():void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $group = Group::factory()->create([
            'dynamic_id' => $dynamic->id,
        ]);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id
        ])->each->refresh();
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/task/{$tasks->last()->id}", []);

        $response->assertJson(['message' => 'The name field is required. The description field is required. The type field is required. The value field is required. The target count field is required. The max count field is required. The rrule field is required. The start field is required. The remove points on failure field is required. The remind field is required. The restrict field is required. The restrict before field is required.']);
    }

    public function test_can_delete_task():void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $tasks = Task::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id
        ])->each->refresh();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/task/{$tasks->last()->id}", []);

        $response->assertJson(['message' => 'success']);
        $this->assertDatabaseMissing('tasks', [
            'id' => $tasks->last()->id
        ]);
    }

    public function test_complete_encourage_task_no_message(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name_sub',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();
        $rewards = Reward::factory()->count(2)->create();
        $rewards->each(fn($reward) => $reward->tasks()->attach($task));

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/complete-task/{$task->id}", []);
        $response->assertJson(['message' => 'NONE']);

        //task count goes up
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'count' => 1,
        ]);
        // sub gets points
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'points' => $task->value,
        ]);
        // rewards given
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $rewards->first()->id,
            'action' => RewardHistory::AUTO_GIVEN,
        ]);
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $rewards->last()->id,
            'action' => RewardHistory::AUTO_GIVEN,
        ]);
    }

    public function test_complete_discourage_task_no_message(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name_sub',
            'role' => User::ROLE_SUB,
            'points' => 3,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();
        $rewards = Reward::factory()->count(2)->create([
            'bank' => 1
        ]);
        $rewards->each(fn($reward) => $reward->tasks()->attach($task));

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/complete-task/{$task->id}", []);
        $response->assertJson(['message' => 'NONE']);

        //task count goes up
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'count' => 1,
        ]);
        // sub loses points
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'points' => 0,
        ]);
        // rewards taken
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $rewards->first()->id,
            'action' => RewardHistory::AUTO_TAKEN,
        ]);
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $rewards->last()->id,
            'action' => RewardHistory::AUTO_TAKEN,
        ]);
    }

    public function test_complete_task_with_message(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name_sub',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();
        $message = Message::factory()->create();
        $message->tasks()->attach($task);

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/complete-task/{$task->id}", []);
        $response->assertJson(['message' => $message->name . "\n\n" . $message->description,]);
    }

    public function test_uncomplete_encourage_task(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name_sub',
            'role' => User::ROLE_SUB,
            'points' => 3,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'count' => 1,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();
        $rewards = Reward::factory()->count(2)->create();
        $rewards->each(fn($reward) => $reward->tasks()->attach($task));

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/uncomplete-task/{$task->id}", []);
        $response->assertJson(['message' => 'success']);

        //task count goes down
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'count' => 0,
        ]);
        // sub loses points
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'points' => 0,
        ]);
        // rewards taken
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $rewards->first()->id,
            'action' => RewardHistory::AUTO_TAKEN,
        ]);
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $rewards->last()->id,
            'action' => RewardHistory::AUTO_TAKEN,
        ]);
    }

    public function test_count_of_remaining_tasks(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        // not done
        Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'count' => 0,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ]);
        Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 1,
            'target_count' => 1,
            'max_count' => 1,
        ]);
        //done
        Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'count' => 1,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ]);
        Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 0,
            'target_count' => 1,
            'max_count' => 1,
        ]);
        // not done, but not due today
        Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'count' => 0,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
            'start' => Carbon::now($dynamic->time_zone)->addDays(1)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->addDays(2)->format('Y-m-d'),
        ]);
        Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 1,
            'target_count' => 1,
            'max_count' => 1,
            'start' => Carbon::now($dynamic->time_zone)->subDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->subDays(1)->format('Y-m-d'),
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson("/api/tasks/remaining", []);
        $response->assertJson(['count' => 10]);
    }

    public function test_can_complete_task_history(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();
        $history = TaskHistory::factory()->create([
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'task_id' => $task->id,
            'was_complete' => 0,
            'count' => 0,
            'target_count' => 1,
        ]);

        $this->assertDatabaseHas('task_histories', [
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'task_id' => $task->id,
            'was_complete' => 0,
            'count' => 0,
            'target_count' => 1,
        ]);

        Sanctum::actingAs($user);
        $this->postJson("/api/complete-task-history/{$history->id}", []);

        $this->assertDatabaseHas('task_histories', [
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'task_id' => $task->id,
            'was_complete' => 1,
            'count' => 1,
            'target_count' => 1,
        ]);
    }

    public function test_can_uncomplete_task_history(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();
        $history = TaskHistory::factory()->create([
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'task_id' => $task->id,
            'was_complete' => 1,
            'count' => 1,
            'target_count' => 1,
        ]);

        $this->assertDatabaseHas('task_histories', [
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'task_id' => $task->id,
            'was_complete' => 1,
            'count' => 1,
            'target_count' => 1,
        ]);

        Sanctum::actingAs($user);
        $this->postJson("/api/uncomplete-task-history/{$history->id}", []);

        $this->assertDatabaseHas('task_histories', [
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'task_id' => $task->id,
            'was_complete' => 0,
            'count' => 0,
            'target_count' => 1,
        ]);
    }

    public function test_can_assign_task_to_group(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $group = Group::factory()->create([
            'dynamic_id' => $dynamic->id,
        ]);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/assign-group/{$task->id}/{$group->id}", []);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'group_id' => $group->id,
        ]);
    }

    public function test_can_unassign_task_to_group(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $group = Group::factory()->create([
            'dynamic_id' => $dynamic->id,
        ]);
        $task = Task::factory()->create([
            'dynamic_id' => $dynamic->id,
            'type' => Task::TYPE_ENCOURAGE,
            'target_count' => 1,
            'max_count' => 1,
            'value' => 3,
            'group_id' => $group->id
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/unassign-group/{$task->id}/{$group->id}", []);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'group_id' => 0,
        ]);
    }

    public function test_can_get_reminders_when_none_set_to_remind(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create([]);
        $dynamic->users()->attach($user->id);
        $tasksDue = Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'remind' => 0,
        ])->each->refresh();
        $tasksNotDueBefore = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->subDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->subDays(1)->format('Y-m-d'),
            'remind' => 0,
        ])->each->refresh();
        $tasksNotDueAfter = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->addDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->addDays(3)->format('Y-m-d'),
            'remind' => 0,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks/reminders');

        $response->assertOk()->assertJson(["reminders" => []]);
    }

    public function test_can_get_reminders_when_some_encourage_tasks_set_to_remind(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create([]);
        $dynamic->users()->attach($user->id);
        $tasksDue = Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s'),
            'type' => Task::TYPE_ENCOURAGE,
        ])->each->refresh();
        $tasksNotDueBefore = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->subDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->subDays(1)->format('Y-m-d'),
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s'),
            'type' => Task::TYPE_ENCOURAGE,
        ])->each->refresh();
        $tasksNotDueAfter = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->addDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->addDays(3)->format('Y-m-d'),
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s'),
            'type' => Task::TYPE_ENCOURAGE,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks/reminders');

        $response->assertOk()->assertJsonCount(10, 'reminders');
    }

    public function test_can_get_reminders_when_some_discourage_tasks_set_to_remind(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create([]);
        $dynamic->users()->attach($user->id);
        $tasksDue = Task::factory()->count(5)->create([
            'dynamic_id' => $dynamic->id,
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s'),
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 1,
            'target_count' => 1,
        ])->each->refresh();
        $tasksNotDueBefore = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->subDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->subDays(1)->format('Y-m-d'),
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s'),
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 1,
            'target_count' => 1,
        ])->each->refresh();
        $tasksNotDueAfter = Task::factory()->count(3)->create([
            'dynamic_id' => $dynamic->id,
            'start' => Carbon::now($dynamic->time_zone)->addDays(2)->format('Y-m-d'),
            'end' => Carbon::now($dynamic->time_zone)->addDays(3)->format('Y-m-d'),
            'remind' => 1,
            'remind_time' => Carbon::createFromTime(rand(0,23), rand(0, 59), rand(0,59))->format('H:m:s'),
            'type' => Task::TYPE_DISCOURAGE,
            'count' => 1,
            'target_count' => 1,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/tasks/reminders');

        $response->assertOk()->assertJsonCount(10, 'reminders');
    }
}
