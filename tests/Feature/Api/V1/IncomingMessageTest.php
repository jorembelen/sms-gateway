<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\IncomingMessage;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncomingMessageTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): array
    {
        return ['X-API-Key' => 'test-api-key-for-testing'];
    }

    private function validPayload(Device $device, array $overrides = []): array
    {
        return array_merge([
            'device_public_id' => $device->public_id,
            'sender'           => '+639123456789',
            'body'             => 'Hello, this is a reply.',
            'received_at'      => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_valid_incoming_message_is_saved_correctly(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/messages/incoming', $this->validPayload($device), $this->withKey());

        $response->assertStatus(201)->assertJsonStructure(['public_id']);

        $this->assertDatabaseHas('incoming_messages', [
            'device_id' => $device->id,
            'sender'    => '+639123456789',
            'body'      => 'Hello, this is a reply.',
        ]);

        // public_id is a valid UUID
        $stored = IncomingMessage::first();
        $this->assertNotNull($stored->public_id);
        $this->assertEquals(36, strlen($stored->public_id));
        $this->assertEquals($stored->public_id, $response->json('public_id'));
    }

    public function test_outbound_message_linked_by_phone_number_match(): void
    {
        $device  = Device::factory()->create(['status' => 'active']);
        $outbound = Message::factory()->create([
            'to'         => '+639123456789',
            'status'     => 'sent',
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson('/api/v1/messages/incoming', $this->validPayload($device, [
            'sender' => '+639123456789',
        ]), $this->withKey());

        $response->assertStatus(201);

        $this->assertDatabaseHas('incoming_messages', [
            'sender'              => '+639123456789',
            'outbound_message_id' => $outbound->id,
        ]);
    }

    public function test_no_link_when_no_matching_outbound_found(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        // Outbound to a different number
        Message::factory()->create(['to' => '+639999999999', 'status' => 'sent']);

        $response = $this->postJson('/api/v1/messages/incoming', $this->validPayload($device, [
            'sender' => '+639123456789',
        ]), $this->withKey());

        $response->assertStatus(201);

        $this->assertDatabaseHas('incoming_messages', [
            'sender'              => '+639123456789',
            'outbound_message_id' => null,
        ]);
    }

    public function test_no_link_when_matching_outbound_is_older_than_24_hours(): void
    {
        $device   = Device::factory()->create(['status' => 'active']);
        $outbound = Message::factory()->create([
            'to'         => '+639123456789',
            'status'     => 'sent',
            'created_at' => now()->subHours(25),
        ]);

        $this->postJson('/api/v1/messages/incoming', $this->validPayload($device, [
            'sender' => '+639123456789',
        ]), $this->withKey());

        $this->assertDatabaseHas('incoming_messages', [
            'sender'              => '+639123456789',
            'outbound_message_id' => null,
        ]);
    }

    public function test_most_recent_outbound_is_linked_when_multiple_exist(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        Message::factory()->create([
            'to'         => '+639123456789',
            'status'     => 'sent',
            'created_at' => now()->subHours(10),
        ]);
        $newest = Message::factory()->create([
            'to'         => '+639123456789',
            'status'     => 'delivered',
            'created_at' => now()->subHours(1),
        ]);

        $this->postJson('/api/v1/messages/incoming', $this->validPayload($device, [
            'sender' => '+639123456789',
        ]), $this->withKey());

        $this->assertDatabaseHas('incoming_messages', [
            'sender'              => '+639123456789',
            'outbound_message_id' => $newest->id,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/messages/incoming', $this->validPayload($device));

        $response->assertStatus(401);
    }

    public function test_wrong_api_key_is_rejected(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson(
            '/api/v1/messages/incoming',
            $this->validPayload($device),
            ['X-API-Key' => 'wrong-key']
        );

        $response->assertStatus(401);
    }

    public function test_missing_sender_returns_422(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/messages/incoming', [
            'device_public_id' => $device->public_id,
            'body'             => 'Hello',
            'received_at'      => now()->toIso8601String(),
        ], $this->withKey());

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors' => ['sender']]);
    }

    public function test_missing_body_returns_422(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/messages/incoming', [
            'device_public_id' => $device->public_id,
            'sender'           => '+639123456789',
            'received_at'      => now()->toIso8601String(),
        ], $this->withKey());

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors' => ['body']]);
    }

    public function test_missing_received_at_returns_422(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/messages/incoming', [
            'device_public_id' => $device->public_id,
            'sender'           => '+639123456789',
            'body'             => 'Hello',
        ], $this->withKey());

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors' => ['received_at']]);
    }

    public function test_missing_device_public_id_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/incoming', [
            'sender'      => '+639123456789',
            'body'        => 'Hello',
            'received_at' => now()->toIso8601String(),
        ], $this->withKey());

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors' => ['device_public_id']]);
    }

    public function test_unregistered_device_public_id_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/incoming', [
            'device_public_id' => '00000000-0000-0000-0000-000000000000',
            'sender'           => '+639123456789',
            'body'             => 'Hello',
            'received_at'      => now()->toIso8601String(),
        ], $this->withKey());

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors' => ['device_public_id']]);
    }

    public function test_invalid_received_at_format_returns_422(): void
    {
        $device = Device::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/messages/incoming', [
            'device_public_id' => $device->public_id,
            'sender'           => '+639123456789',
            'body'             => 'Hello',
            'received_at'      => 'not-a-date',
        ], $this->withKey());

        $response->assertStatus(422)->assertJsonStructure(['error', 'errors' => ['received_at']]);
    }

    public function test_rate_limiting_fires_on_excessive_requests(): void
    {
        $device = Device::factory()->create(['status' => 'active']);
        $payload = $this->validPayload($device);

        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/v1/messages/incoming', $payload, $this->withKey());
        }

        $response = $this->postJson('/api/v1/messages/incoming', $payload, $this->withKey());
        $response->assertStatus(429);
    }
}
