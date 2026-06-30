<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const REGISTER_URL = '/api/v1/devices/register';

    private function withKey(): array
    {
        return ['X-API-Key' => 'test-api-key-for-testing'];
    }

    public function test_registers_new_device_successfully(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => 'abc123-fcm-token',
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonStructure(['public_id', 'status', 'last_seen_at'])
            ->assertJsonFragment(['status' => 'active']);

        $this->assertDatabaseHas('devices', [
            'fcm_token' => 'abc123-fcm-token',
            'status' => 'active',
        ]);
    }

    public function test_registration_response_does_not_expose_internal_id(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => 'abc123-fcm-token',
        ], $this->withKey());

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('id', $response->json());
    }

    public function test_registration_returns_valid_uuid_as_public_id(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => 'abc123-fcm-token',
        ], $this->withKey());

        $response->assertStatus(200);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $response->json('public_id'),
        );
    }

    public function test_re_registering_same_token_updates_existing_record(): void
    {
        $device = Device::factory()->inactive()->create(['fcm_token' => 'existing-token']);

        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => 'existing-token',
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonFragment(['public_id' => $device->public_id, 'status' => 'active']);

        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseHas('devices', ['fcm_token' => 'existing-token', 'status' => 'active']);
    }

    public function test_response_contains_last_seen_at_timestamp(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => 'token-ts',
        ], $this->withKey());

        $response->assertStatus(200);
        $this->assertNotNull($response->json('last_seen_at'));
    }

    public function test_missing_fcm_token_returns_422(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['fcm_token']]);
    }

    public function test_fcm_token_exceeding_255_chars_returns_422(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => str_repeat('a', 256),
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['fcm_token']]);
    }

    public function test_token_at_max_length_is_accepted(): void
    {
        $response = $this->postJson(self::REGISTER_URL, [
            'fcm_token' => str_repeat('a', 255),
        ], $this->withKey());

        $response->assertStatus(200);
    }
}
