<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-api-key-for-testing';

    private function apiKey(): array
    {
        return ['X-API-Key' => self::KEY];
    }

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => 'Hello',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or missing API key.']);
    }

    public function test_wrong_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => 'Hello',
        ], ['X-API-Key' => 'wrong-key']);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or missing API key.']);
    }

    public function test_empty_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => 'Hello',
        ], ['X-API-Key' => '']);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid or missing API key.']);
    }

    public function test_valid_api_key_passes_authentication(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => 'some-token',
        ], $this->apiKey());

        $response->assertStatus(200);
    }

    public function test_api_responses_include_security_headers(): void
    {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => 'some-token',
        ], $this->apiKey());

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_unauthenticated_responses_also_include_security_headers(): void
    {
        $response = $this->postJson('/api/v1/messages/send', [
            'to' => '+639123456789',
            'content' => 'Hello',
        ]);

        $response->assertStatus(401);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
