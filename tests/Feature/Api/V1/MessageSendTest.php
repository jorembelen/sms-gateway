<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendSmsJob;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MessageSendTest extends TestCase
{
    use RefreshDatabase;

    private function withKey(): array
    {
        return ['X-API-Key' => 'test-api-key-for-testing'];
    }

    public function test_queues_message_and_returns_201(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => 'Hello, world!',
        ], $this->withKey());

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'status', 'to', 'content'])
            ->assertJsonFragment([
                'to' => '+639123456789',
                'content' => 'Hello, world!',
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('messages', [
            'to' => '+639123456789',
            'status' => 'pending',
        ]);
    }

    public function test_dispatches_send_sms_job_with_correct_message_id(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => 'Test message',
        ], $this->withKey());

        Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) {
            return $job->messageId === Message::first()->id;
        });
    }

    public function test_missing_to_field_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'content' => 'Hello',
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['to']]);
    }

    public function test_missing_content_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['content']]);
    }

    public function test_invalid_phone_format_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => 'not-a-phone',
            'content' => 'Hello',
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['to']]);
    }

    public function test_phone_too_short_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '123',
            'content' => 'Hello',
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['to']]);
    }

    public function test_content_exceeding_320_chars_returns_422(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => str_repeat('a', 321),
        ], $this->withKey());

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors' => ['content']]);
    }

    public function test_phone_without_plus_prefix_is_accepted(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '639123456789',
            'content' => 'Hello',
        ], $this->withKey());

        $response->assertStatus(201);
    }

    public function test_whitespace_around_phone_number_is_trimmed(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '  +639123456789  ',
            'content' => 'Hello',
        ], $this->withKey());

        $response->assertStatus(201)
            ->assertJsonFragment(['to' => '+639123456789']);
    }

    public function test_whitespace_around_content_is_trimmed(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => '  Hello world  ',
        ], $this->withKey());

        $response->assertStatus(201)
            ->assertJsonFragment(['content' => 'Hello world']);
    }
}
