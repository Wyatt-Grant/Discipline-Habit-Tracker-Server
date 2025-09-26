<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Punishment;
use App\Models\PunishmentHistory;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PunishmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_punishmnets(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $punishments = Punishment::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/punishments');

        $response->assertOk()->assertJson(["punishments" => $punishments->toArray()]);
    }

    public function test_can_create_punishment(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/punishments', [
            'name' => 'new punishment',
            'description' => 'new punishment desc',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('punishments', [
            'name' => 'new punishment',
            'description' => 'new punishment desc',
        ]);
    }

    public function test_can_update_punishment(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $punishment = Punishment::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/punishment/{$punishment->id}", [
            'name' => 'new punishment',
            'description' => 'new punishment desc',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('punishments', [
            'id' => $punishment->id,
            'name' => 'new punishment',
            'description' => 'new punishment desc',
        ]);
    }

    public function test_can_create_returns_errors_with_invalid_info(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/punishments', []);

        $response->assertJson(["message" => 'The name field is required. The description field is required.']);
    }

    public function test_can_delete_punishment(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $punishment = Punishment::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/punishment/{$punishment->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseMissing('punishments', [
            'id' => $punishment->id,
        ]);
    }

    public function test_can_add_punishment(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $punishment = Punishment::factory()->create([
            'value' => 3,
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/add-punishment/{$punishment->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('punishments', [
            'id' => $punishment->id,
            'value' => 4,
        ]);
        $this->assertDatabaseHas('punishment_histories', [
            'punishment_id' => $punishment->id,
            'date' => Carbon::now($dynamic->time_zone)->format('Y-m-d'),
            'action' => PunishmentHistory::ASSIGNED,
        ]);
    }

    public function test_can_remove_punishment(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $punishment = Punishment::factory()->create([
            'value' => 3,
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/remove-punishment/{$punishment->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('punishments', [
            'id' => $punishment->id,
            'value' => 2,
        ]);
        $this->assertDatabaseHas('punishment_histories', [
            'punishment_id' => $punishment->id,
            'date' => Carbon::now($dynamic->time_zone)->format('Y-m-d'),
            'action' => PunishmentHistory::FORGIVEN,
        ]);
    }

    public function test_can_get_punishments_assigned_count(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $count = 3;
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $punishments = Punishment::factory()->count(5)->create([
            'value' => $count,
            'dynamic_id' => $dynamic->id,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/punishments/assigned', []);

        $response->assertOk()->assertJson(["count" => $punishments->count() * $count]);
    }

    public function test_can_assign_task_to_punishment(): void
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
        $punishment = Punishment::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/assign-punishment/{$punishment->id}/{$task->id}", []);

        $this->assertDatabaseHas('punishments_tasks', [
            'task_id' => $task->id,
            'punishment_id' => $punishment->id,
        ]);
    }

    public function test_can_unassign_task_to_punishment(): void
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
        $punishment = Punishment::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/unassign-punishment/{$punishment->id}/{$task->id}", []);

        $this->assertDatabaseMissing('punishments_tasks', [
            'task_id' => $task->id,
            'punishment_id' => $punishment->id,
        ]);
    }
}
