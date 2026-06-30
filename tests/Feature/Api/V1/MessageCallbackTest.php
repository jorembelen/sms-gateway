<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): array
    {
        return ['X-API-Key' => 'test-api-key-for-testing'];
    }

    public function test_callback_marks_message_as_sent(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->create(['device_id' => $device->id]);

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'sent',
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $message->id, 'status' => 'sent']);

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'sent']);
    }

    public function test_callback_marks_message_as_delivered(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->create(['device_id' => $device->id]);

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'delivered',
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'delivered']);
    }

    public function test_callback_marks_message_as_failed_with_reason(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->create(['device_id' => $device->id]);
        $reason = 'SIM card not found';

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'failed',
            'failure_reason' => $reason,
        ], $this->withKey());

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'failed', 'failure_reason' => $reason]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function test_callback_from_wrong_device_returns_403(): void
    {
        $assignedDevice = Device::factory()->active()->create();
        $otherDevice = Device::factory()->active()->create();
        $message = Message::factory()->pending()->create(['device_id' => $assignedDevice->id]);

        $response = $this->postJson("/api/v1/devices/{$otherDevice->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'sent',
        ], $this->withKey());

        $response->assertStatus(403)
            ->assertJson(['error' => 'Message not assigned to this device.']);
    }

    public function test_callback_associates_unassigned_message_with_reporting_device(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->withoutDevice()->create();

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'sent',
        ], $this->withKey());

        $response->assertStatus(200);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'device_id' => $device->id]);
    }

    public function test_callback_updates_device_last_seen_at(): void
    {
        $device = Device::factory()->active()->create(['last_seen_at' => now()->subHour()]);
        $message = Message::factory()->pending()->create(['device_id' => $device->id]);

        $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'sent',
        ], $this->withKey());

        $device->refresh();
        $this->assertTrue($device->last_seen_at->isAfter(now()->subMinute()));
    }

    public function test_non_existent_message_id_returns_422(): void
    {
        $device = Device::factory()->active()->create();

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => 99999,
            'status' => 'sent',
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['message_id']]);
    }

    public function test_invalid_status_value_returns_422(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->create(['device_id' => $device->id]);

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'invalid-status',
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['status']]);
    }

    public function test_non_existent_device_returns_404(): void
    {
        $message = Message::factory()->withoutDevice()->create();

        $response = $this->postJson('/api/v1/devices/00000000-0000-0000-0000-000000000000/callback', [
            'message_id' => $message->id,
            'status' => 'sent',
        ], $this->withKey());

        $response->assertStatus(404);
    }

    public function test_failure_reason_exceeding_255_chars_returns_422(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->create(['device_id' => $device->id]);

        $response = $this->postJson("/api/v1/devices/{$device->public_id}/callback", [
            'message_id' => $message->id,
            'status' => 'failed',
            'failure_reason' => str_repeat('a', 256),
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['failure_reason']]);
    }
}
