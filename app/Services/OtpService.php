<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\Message;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    private const MAX_SENDS_PER_WINDOW = 3;
    private const SEND_WINDOW_SECONDS = 600; // 10 minutes
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const OTP_TTL_MINUTES = 5;

    // External gateway used when SMS_GATEWAY_URL is set (e.g. for local dev testing).
    private const EXTERNAL_GATEWAY_URL = 'https://sms.joremapps.com';

    /**
     * Generate a new OTP, store it hashed, and dispatch an SMS.
     *
     * Returns false when the user has hit the rate limit (3 sends per 10 min).
     */
    public function generateAndSend(User $user): bool
    {
        $rateLimitKey = "otp_send:{$user->id}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_SENDS_PER_WINDOW)) {
            return false;
        }

        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        $body = "Your admin login code is: {$plainCode}. Expires in 5 minutes. Do not share this code.";

        $this->dispatchSms($user->phone_number, $body);

        // Break-glass fallback: log plaintext OTP when opted in via env (default off).
        if (config('app.otp_log_fallback', false)) {
            Log::info("[OTP FALLBACK] Code for user {$user->email}: {$plainCode}");
        }

        RateLimiter::hit($rateLimitKey, self::SEND_WINDOW_SECONDS);

        return true;
    }

    /**
     * Send an SMS either via the external gateway API or the local FCM pipeline.
     *
     * External mode is used when SMS_GATEWAY_API_KEY is set in .env, which routes
     * the message through sms.joremapps.com (useful in local dev where no device
     * is registered locally).
     */
    private function dispatchSms(string $to, string $content): void
    {
        $apiKey = config('services.sms_gateway.api_key');

        if ($apiKey) {
            Http::withHeaders(['X-API-Key' => $apiKey])
                ->post(self::EXTERNAL_GATEWAY_URL . '/api/v1/messages/send', [
                    'to'      => $to,
                    'content' => $content,
                ]);

            return;
        }

        // Local pipeline: store message record and queue the FCM job.
        $message = Message::create(['to' => $to, 'content' => $content]);
        SendSmsJob::dispatch($message->id);
    }

    /**
     * Verify a submitted code against the user's latest active OTP.
     *
     * Returns one of: 'success', 'invalid', 'expired', 'locked', 'none'
     */
    public function verify(User $user, string $submittedCode): string
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if ($otp === null) {
            return 'none';
        }

        if ($otp->isExpired()) {
            return 'expired';
        }

        if ($otp->isLockedOut()) {
            return 'locked';
        }

        $otp->increment('attempts');

        if (! Hash::check($submittedCode, $otp->code)) {
            if ($otp->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                return 'locked';
            }

            return 'invalid';
        }

        $otp->update(['consumed_at' => now()]);

        return 'success';
    }

    public function rateLimitKey(User $user): string
    {
        return "otp_send:{$user->id}";
    }

    public function secondsUntilRateLimitClears(User $user): int
    {
        return RateLimiter::availableIn($this->rateLimitKey($user));
    }
}
