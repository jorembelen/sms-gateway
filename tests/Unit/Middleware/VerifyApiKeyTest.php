<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class VerifyApiKeyTest extends TestCase
{
    private const CONFIGURED_KEY = 'super-secret-key';

    private function passThrough(string|null $providedKey, string|null $configuredKey = self::CONFIGURED_KEY): int
    {
        config(['services.gateway.api_key' => $configuredKey]);

        $request = Request::create('/api/test', 'POST');
        if ($providedKey !== null) {
            $request->headers->set('X-API-Key', $providedKey);
        }

        $response = (new VerifyApiKey())->handle(
            $request,
            fn ($_req) => new Response('OK', 200)
        );

        return $response->getStatusCode();
    }

    public function test_allows_correct_api_key(): void
    {
        $this->assertEquals(200, $this->passThrough(self::CONFIGURED_KEY));
    }

    public function test_rejects_missing_api_key(): void
    {
        $this->assertEquals(401, $this->passThrough(null));
    }

    public function test_rejects_wrong_api_key(): void
    {
        $this->assertEquals(401, $this->passThrough('wrong-key'));
    }

    public function test_rejects_empty_string_api_key(): void
    {
        $this->assertEquals(401, $this->passThrough(''));
    }

    public function test_rejects_when_no_key_is_configured(): void
    {
        $this->assertEquals(401, $this->passThrough(self::CONFIGURED_KEY, null));
    }

    public function test_rejects_when_configured_key_is_empty_string(): void
    {
        $this->assertEquals(401, $this->passThrough('anything', ''));
    }

    public function test_comparison_is_case_sensitive(): void
    {
        $this->assertEquals(401, $this->passThrough(strtoupper(self::CONFIGURED_KEY)));
    }
}
