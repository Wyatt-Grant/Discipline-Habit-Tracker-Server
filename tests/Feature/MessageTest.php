<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Message;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_messages(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $messages = Message::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/messages');

        $response->assertOk()->assertJson(["messages" => $messages->toArray()]);
    }

    public function test_can_create_message(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/messages', [
            'name' => 'new Message',
            'description' => 'new Message desc',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('messages', [
            'name' => 'new Message',
            'description' => 'new Message desc',
        ]);
    }

    public function test_can_update_message(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $message = Message::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/message/{$message->id}", [
            'name' => 'new Message',
            'description' => 'new Message desc',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'name' => 'new Message',
            'description' => 'new Message desc',
        ]);
    }

    public function test_can_create_message_returns_errors_with_invalid_info(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/messages', []);

        $response->assertJson(["message" => 'The header field is required. The subtext field is required.']);
    }

    public function test_can_delete_message(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $message = Message::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/message/{$message->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    public function test_can_assign_task_to_message(): void
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
        $message = Message::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/assign-message/{$message->id}/{$task->id}", []);

        $this->assertDatabaseHas('messages_tasks', [
            'task_id' => $task->id,
            'message_id' => $message->id,
        ]);
    }

    public function test_can_unassign_task_to_message(): void
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
        $message = Message::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $this->postJson("/api/unassign-message/{$message->id}/{$task->id}", []);

        $this->assertDatabaseMissing('messages_tasks', [
            'task_id' => $task->id,
            'message_id' => $message->id,
        ]);
    }
}
