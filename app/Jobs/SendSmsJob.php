<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Message;
use App\Services\Fcm\FcmException;
use App\Services\Fcm\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of attempts for transient FCM errors.
     */
    public int $tries = 3;

    /**
     * Backoff (seconds) between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30];

    public function __construct(public int $messageId) {}

    public function handle(FcmService $fcm): void
    {
        $message = Message::find($this->messageId);

        if ($message === null) {
            Log::warning("SendSmsJob: message {$this->messageId} no longer exists.");

            return;
        }

        // Don't re-send a message that has already moved past pending.
        if ($message->status !== 'pending') {
            return;
        }

        $device = $message->device_id
            ? Device::where('id', $message->device_id)->where('status', 'active')->whereNotNull('fcm_token')->first()
            : Device::where('status', 'active')->whereNotNull('fcm_token')->latest('last_seen_at')->first();

        if ($device === null) {
            $message->update([
                'status' => 'failed',
                'failure_reason' => 'no active device',
            ]);

            return;
        }

        $message->update(['device_id' => $device->id]);

        try {
            $fcm->sendDataMessage($device->fcm_token, [
                'message_id' => (string) $message->id,
                'to' => $message->to,
                'content' => $message->content,
            ]);

            // Success: leave as pending. The phone reports sent/delivered via callback.
            Log::info("SendSmsJob: FCM data message dispatched for message {$message->id} to device {$device->id}.");
        } catch (FcmException $e) {
            if ($e->tokenInvalid) {
                // Permanent failure — fail the message and deactivate the device.
                $message->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
                $device->update(['status' => 'inactive']);

                return;
            }

            // Transient failure — log and rethrow so the queue retries with backoff.
            Log::warning("SendSmsJob: transient FCM error for message {$message->id} (attempt {$this->attempts()}): {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Final failure after all retries are exhausted.
     */
    public function failed(\Throwable $e): void
    {
        $message = Message::find($this->messageId);

        if ($message !== null && $message->status === 'pending') {
            $message->update([
                'status' => 'failed',
                'failure_reason' => 'FCM send failed: '.$e->getMessage(),
            ]);
        }
    }
}
