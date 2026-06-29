<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): array
    {
        return ['X-API-Key' => 'test-api-key-for-testing'];
    }

    public function test_registers_new_device_successfully(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => 'abc123-fcm-token',
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'status', 'last_seen_at'])
            ->assertJsonFragment(['status' => 'active']);

        $this->assertDatabaseHas('devices', [
            'fcm_token' => 'abc123-fcm-token',
            'status' => 'active',
        ]);
    }

    public function test_re_registering_same_token_updates_existing_record(): void
    {
        $device = Device::factory()->inactive()->create(['fcm_token' => 'existing-token']);

        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => 'existing-token',
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $device->id, 'status' => 'active']);

        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseHas('devices', ['fcm_token' => 'existing-token', 'status' => 'active']);
    }

    public function test_response_contains_last_seen_at_timestamp(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => 'token-ts',
        ], $this->withKey());

        $response->assertStatus(200);
        $this->assertNotNull($response->json('last_seen_at'));
    }

    public function test_missing_fcm_token_returns_422(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['fcm_token']]);
    }

    public function test_fcm_token_exceeding_255_chars_returns_422(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => str_repeat('a', 256),
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['fcm_token']]);
    }

    public function test_token_at_max_length_is_accepted(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => str_repeat('a', 255),
        ], $this->withKey());

        $response->assertStatus(200);
    }
}
