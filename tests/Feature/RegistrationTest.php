<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_new_user_and_create_dynamic(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'password' => 'testpass',
            'role' => User::ROLE_DOM,
            'device_name' => 'required',
            'create_dynamic' => 1,
            'dynamic_name' => 'test dynamic',
            'dynamic_time_zone' => 'Europe/London',
        ]);

        $response->assertJson(['message' => 'success']);
    }

    public function test_can_register_new_user_and_join_dynamic(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        $response = $this->postJson('/api/register', [
            'name' => 'test user2',
            'user_name' => 'test_user_name2',
            'password' => 'password',
            'role' => User::ROLE_SUB,
            'device_name' => 'required',
            'create_dynamic' => 0,
            'dynamic_uuid' => $dynamic->UUID,
        ]);

        $response->assertJson(['message' => 'success']);
    }

    public function test_register_returns_errors_with_invalid_info(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        $response = $this->postJson('/api/register', [
            'name' => 'test user',
            'user_name' => 'test_user_name',
            'role' => User::ROLE_SUB,
            'device_name' => 'required',
            'create_dynamic' => 0,
            'dynamic_uuid' => $dynamic->UUID,
        ]);

        $response->assertJson(['message' => 'The user name has already been taken. The password field is required.']);
    }
}
