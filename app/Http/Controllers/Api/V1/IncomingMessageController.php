<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomingMessageRequest;
use App\Models\Device;
use App\Models\IncomingMessage;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class IncomingMessageController extends Controller
{
    /**
     * Record an SMS reply received by a registered device.
     */
    public function store(StoreIncomingMessageRequest $request): JsonResponse
    {
        $device = Device::where('public_id', $request->validated('device_public_id'))->firstOrFail();

        $sender = $request->validated('sender');

        // Best-effort link: find the most recent outbound message to this sender within 24 h.
        // NOTE: matching is exact-string only — numbers like +63... vs 63... will not match.
        // See flag in PR description for production normalisation options.
        $outboundMessage = Message::where('to', $sender)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->latest()
            ->first();

        $incoming = IncomingMessage::create([
            'device_id'           => $device->id,
            'sender'              => $sender,
            'body'                => $request->validated('body'),
            'received_at'         => Carbon::parse($request->validated('received_at')),
            'outbound_message_id' => $outboundMessage?->id,
        ]);

        return response()->json([
            'public_id' => $incoming->public_id,
        ], 201);
    }
}
