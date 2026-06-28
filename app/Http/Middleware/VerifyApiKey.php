<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Reject any request whose X-API-Key header does not match the configured
     * gateway key. Comparison is timing-safe.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.gateway.api_key');
        $provided = $request->header('X-API-Key');

        if (empty($expected) || ! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'Invalid or missing API key.'], 401);
        }

        return $next($request);
    }
}
