<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_groups(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $groups = Group::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh();

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/groups');

        $response->assertOk()->assertJson(["groups" => $groups->toArray()]);
    }

    public function test_can_create_group(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/groups', [
            'name' => 'new Group desc',
            'color' => 'FF00FF',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('groups', [
            'name' => 'new Group desc',
            'color' => 'FF00FF',
        ]);
    }

    public function test_can_update_group(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $group = Group::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->putJson("/api/group/{$group->id}", [
            'name' => 'new Group desc',
            'color' => 'FF00FF',
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'new Group desc',
            'color' => 'FF00FF',
        ]);
    }

    public function test_can_update_group_sort_order(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $groups = Group::factory()->count(10)->create([
            'dynamic_id' => $dynamic->id,
        ])->each->refresh()->shuffle();

        Sanctum::actingAs($user);
        $response = $this->postJson("/api/sort-groups", [
            'sorted_group_ids' => $groups->pluck('id')->implode(','),
        ]);

        $response->assertOk()->assertJson(["message" => 'success']);
        $groups->each(function($group, $i) {
            $this->assertDatabaseHas('groups', [
                'id' => $group->id,
                'sort_order' => $i + 1,
            ]);
        });
    }

    public function test_can_create_group_returns_errors_with_invalid_info(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/groups', []);

        $response->assertJson(["message" => 'The name field is required. The color field is required.']);
    }

    public function test_can_delete_group(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);
        $group = Group::factory()->create([
            'dynamic_id' => $dynamic->id,
        ])->refresh();

        Sanctum::actingAs($user);
        $response = $this->deleteJson("/api/group/{$group->id}", []);

        $response->assertOk()->assertJson(["message" => 'success']);
        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
        ]);
    }
}
