<?php

namespace Tests\Feature;

use App\Models\Dynamic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_generate_auth_token_with_non_existent_cred(): void
    {
        $response = $this->postJson('/api/token', [
            'user_name' => 'invalid_user',
            'password' => 'testpass',
            'device_name' => 'required',
            'version' => 2,
        ]);

        $response->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_cannot_generate_auth_token_with_invalid_cred(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        $response = $this->postJson('/api/token', [
            'user_name' => 'invalid_user',
            'password' => 'testpass',
            'device_name' => 'test_device',
            'version' => 2,
        ]);

        $response->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_can_generate_auth_token(): void
    {
        $user = User::factory()->create([
            'name' => 'test user',
            'user_name' => 'test_user_name',
        ]);
        $dynamic = Dynamic::factory()->create();
        $dynamic->users()->attach($user->id);

        $response = $this->postJson('/api/token', [
            'user_name' => 'test_user_name',
            'password' => 'password',
            'device_name' => 'test_device',
            'version' => 2,
        ]);
        $user->refresh();

        $response->assertJsonFragment([
            'user' => $user->toArray(),
        ]);
    }
}
