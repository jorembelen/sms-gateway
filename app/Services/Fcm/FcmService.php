<?php

namespace App\Services\Fcm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Minimal client for the FCM HTTP v1 API.
 *
 * We chose raw HTTP v1 over kreait/firebase-php because that SDK is not
 * installable on PHP 8.4 (it pulls in lcobucci/jwt 5.4+, which requires
 * ext-sodium, plus a php-jwt version blocked by a security advisory). The HTTP
 * v1 flow is small and self-contained: build a service-account JWT, exchange it
 * for a short-lived OAuth2 access token, then POST the message. The access
 * token is cached so we authenticate once per hour, not once per send.
 */
class FcmService
{
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const CACHE_KEY = 'fcm_access_token';

    /**
     * Send a data-only message to a device token.
     *
     * Data-only (no "notification" key) is required so the Flutter app can
     * process it while backgrounded or terminated.
     *
     * @param  array<string, string>  $data
     *
     * @throws FcmException
     */
    public function sendDataMessage(string $token, array $data): void
    {
        $credentials = $this->credentials();
        $accessToken = $this->accessToken($credentials);
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);

        if (empty($projectId)) {
            throw new FcmException('Firebase project_id is not configured.');
        }

        // FCM data payloads must be string => string.
        $stringData = array_map(static fn ($v) => (string) $v, $data);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'data' => $stringData,
                    'android' => [
                        // High priority so the device wakes to handle the SMS.
                        'priority' => 'high',
                    ],
                ],
            ]);

        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $error = $response->json('error.message') ?? $response->body();
        $fcmStatus = $response->json('error.status');

        // UNREGISTERED / INVALID_ARGUMENT (404/400) => the token is dead.
        $tokenInvalid = in_array($status, [400, 401, 403, 404], true)
            || in_array($fcmStatus, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true);

        throw new FcmException("FCM send failed ({$status}): {$error}", $tokenInvalid);
    }

    /**
     * Load and decode the service account JSON.
     *
     * @return array<string, mixed>
     *
     * @throws FcmException
     */
    private function credentials(): array
    {
        $path = config('services.firebase.credentials');

        if (empty($path)) {
            throw new FcmException('FIREBASE_CREDENTIALS_PATH is not configured.');
        }

        // Allow paths relative to the project root.
        if (! $this->isAbsolutePath($path)) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            throw new FcmException("Firebase credentials file not found at: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            throw new FcmException('Firebase credentials file is invalid.');
        }

        return $decoded;
    }

    /**
     * Get a cached or fresh OAuth2 access token for the service account.
     *
     * @param  array<string, mixed>  $credentials
     *
     * @throws FcmException
     */
    private function accessToken(array $credentials): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $jwt = $this->buildSignedJwt($credentials);

        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error_description') ?? $response->body();

            throw new FcmException("Failed to obtain FCM access token: {$error}");
        }

        $accessToken = (string) $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);

        // Refresh a minute early to avoid edge-of-expiry failures.
        Cache::put(self::CACHE_KEY, $accessToken, max(60, $expiresIn - 60));

        return $accessToken;
    }

    /**
     * Build and RS256-sign the assertion JWT from the service account key.
     *
     * @param  array<string, mixed>  $credentials
     *
     * @throws FcmException
     */
    private function buildSignedJwt(array $credentials): string
    {
        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_ENDPOINT,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signingInput = implode('.', $segments);
        $signature = '';

        $ok = openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        if (! $ok) {
            throw new FcmException('Failed to sign FCM JWT with the service account private key.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }
}
