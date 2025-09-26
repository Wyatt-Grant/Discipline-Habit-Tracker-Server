<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DynamicTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_dynamic_info(): void
    {
        $user1 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user1->id);
        $dynamic->users()->attach($user2->id);

        Sanctum::actingAs($user1);
        $response = $this->get('api/dynamic');

        $response->assertStatus(200)->assertJson([
            'dynamic' => [
                'id' => $dynamic->id,
                'name' => $dynamic->name,
                'UUID' => $dynamic->UUID,
                'sub' => $user2->name,
                'dom' => $user1->name,
                'time_zone' => $dynamic->time_zone,
                'default_reward_emojis' => $dynamic->default_reward_emojis
            ]
        ]);
    }

    public function test_can_update_dynamic_info(): void
    {
        $user1 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user1->id);
        $dynamic->users()->attach($user2->id);

        Sanctum::actingAs($user1);
        $response = $this->putJson("api/dynamic/{$dynamic->id}", [
            'name' => 'new name',
            'time_zone' => 'America/Regina',
            'default_reward_emojis' => '👑🤮🍏',
            'sub' => 'new sub',
            'dom' => 'new dom',
        ]);

        $response->assertOK();
        $this->assertDatabaseHas('dynamics', [
            'name' => 'new name',
            'time_zone' => 'America/Regina',
            'default_reward_emojis' => '👑🤮🍏',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user1->id,
            'name' => 'new dom',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'name' => 'new sub',
        ]);
    }

    public function test_update_dynamic_returns_errors_with_invalid_info(): void
    {
        $user1 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_DOM,
        ]);
        $user2 = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name2',
            'role' => User::ROLE_SUB,
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user1->id);
        $dynamic->users()->attach($user2->id);

        Sanctum::actingAs($user1);
        $response = $this->putJson("api/dynamic/{$dynamic->id}", []);

        $response->assertJson(['message' => 'The name field is required. The time zone field is required. The default reward emojis field is required. The sub field is required. The dom field is required.']);
    }
}
