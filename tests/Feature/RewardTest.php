<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Reward;
use App\Models\RewardHistory;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_rewards(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $rewards = Reward::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/rewards');

        $response->assertOk()->assertJson(["rewards" => $rewards->toArray()]);
    }

    public function test_can_create_reward(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/rewards', [
            'name' => 'new Reward',
            'description' => 'new Reward desc',
            'value' => 2,
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('rewards', [
            'name' => 'new Reward',
            'description' => 'new Reward desc',
            'value' => 2,
        ]);
    }

    public function test_can_update_reward(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $reward = Reward::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/reward/{$reward->id}", [
            'name' => 'new Reward',
            'description' => 'new Reward desc',
            'value' => 2,
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'name' => 'new Reward',
            'description' => 'new Reward desc',
            'value' => 2,
        ]);
    }

    public function test_can_create_reward_returns_errors_with_invalid_info(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/rewards', []);

        $response->assertJson(["message" => 'The name field is required. The description field is required. The value field is required.']);
    }

    public function test_can_delete_reward(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $reward = Reward::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/reward/{$reward->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseMissing('rewards', [
            'id' => $reward->id,
        ]);
    }

    public function test_can_add_reward(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $reward = Reward::factory()->create([
            'bank' => 3,
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/add-reward/{$reward->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'bank' => 4,
        ]);
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $reward->id,
            'date' => Carbon::now($dynamic->time_zone)->format('Y-m-d'),
            'action' => RewardHistory::GIVEN,
        ]);
    }

    public function test_can_remove_reward(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $reward = Reward::factory()->create([
            'bank' => 3,
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/remove-reward/{$reward->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('rewards', [
            'id' => $reward->id,
            'bank' => 2,
        ]);
        $this->assertDatabaseHas('reward_histories', [
            'reward_id' => $reward->id,
            'date' => Carbon::now($dynamic->time_zone)->format('Y-m-d'),
            'action' => RewardHistory::TAKEN,
        ]);
    }

    public function test_can_get_rewards_in_bank(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $count = 3;
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $rewards = Reward::factory()->count(5)->create([
            'bank' => $count,
            'dynamic_id' => $dynamic->id,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/bank', []);

        $response->assertOk()->assertJson(["count" => $rewards->count() * $count]);
    }

    public function test_can_get_points(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
            'points' => 10
        ]);
        $count = 3;
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/points', []);

        $response->assertOk()->assertJson(["points" => 10]);
    }

    public function test_can_add_points(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
            'points' => 10
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/add-point', []);

        $response->assertOk()->assertJson(["points" => 11]);
    }

    public function test_can_remove_points(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
            'points' => 10
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $dynamic->users()->attach($user2->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/remove-point', []);

        $response->assertOk()->assertJson(["points" => 9]);
    }

    public function test_can_assign_task_to_reward(): void
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
        $reward = Reward::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/assign-reward/{$reward->id}/{$task->id}", []);

        $this->assertDatabaseHas('rewards_tasks', [
            'task_id' => $task->id,
            'reward_id' => $reward->id,
        ]);
    }

    public function test_can_unassign_task_to_reward(): void
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
        $reward = Reward::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/unassign-reward/{$reward->id}/{$task->id}", []);

        $this->assertDatabaseMissing('rewards_tasks', [
            'task_id' => $task->id,
            'reward_id' => $reward->id,
        ]);
    }
}
