<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageCallbackRequest;
use App\Http\Requests\RegisterDeviceRequest;
use App\Models\Device;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    /**
     * Register (or re-register) a device by its FCM token.
     *
     * Written generically: each unique fcm_token maps to one device row, so a
     * second physical device registering simply creates/updates its own record.
     */
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $device = Device::firstOrCreate(
            ['fcm_token' => $request->validated('fcm_token')],
            ['status' => 'active', 'last_seen_at' => now()],
        );

        // Always refresh last_seen_at; do NOT touch status so admin toggles persist.
        $device->update(['last_seen_at' => now()]);

        return response()->json([
            'public_id' => $device->public_id,
            'status' => $device->status,
            'last_seen_at' => $device->last_seen_at,
        ], 200);
    }

    /**
     * Callback from a device reporting the delivery status of a message.
     */
    public function callback(MessageCallbackRequest $request, Device $device): JsonResponse
    {
        /** @var Message $message */
        $message = Message::findOrFail($request->validated('message_id'));

        if ($message->device_id !== null && $message->device_id !== $device->id) {
            return response()->json(['error' => 'Message not assigned to this device.'], 403);
        }

        $message->update([
            'status' => $request->validated('status'),
            'failure_reason' => $request->validated('failure_reason'),
            // Associate the message with the reporting device if not already set.
            'device_id' => $message->device_id ?? $device->id,
        ]);

        $device->update(['last_seen_at' => now()]);

        return response()->json([
            'id' => $message->id,
            'status' => $message->status,
            'failure_reason' => $message->failure_reason,
            'to' => $message->to,
        ], 200);
    }
}
