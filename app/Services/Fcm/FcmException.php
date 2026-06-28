<?php

namespace App\Services\Fcm;

use RuntimeException;

/**
 * Thrown when an FCM operation fails. $tokenInvalid distinguishes a permanent
 * failure (bad/expired token — the device should be deactivated) from a
 * transient one (network/5xx — worth retrying).
 */
class FcmException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $tokenInvalid = false,
    ) {
        parent::__construct($message);
    }
}
