<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Rule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_rules(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $rules = Rule::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/rules');

        $response->assertOk()->assertJson(["rules" => $rules->toArray()]);
    }

    public function test_can_create_rule(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/rules', [
            'description' => 'new Rule desc',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('rules', [
            'description' => 'new Rule desc',
        ]);
    }

    public function test_can_update_rule(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $rule = Rule::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/rule/{$rule->id}", [
            'description' => 'new Rule desc',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'description' => 'new Rule desc',
        ]);
    }

    public function test_can_create_rule_returns_errors_with_invalid_info(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/rules', []);

        $response->assertJson(["message" => 'The rule field is required.']);
    }

    public function test_can_delete_rule(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $rule = Rule::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/rule/{$rule->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseMissing('rules', [
            'id' => $rule->id,
        ]);
    }
}
