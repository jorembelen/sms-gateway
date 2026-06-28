<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Jobs\SendSmsJob;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    /**
     * Queue an SMS for delivery via the registered device.
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        $message = Message::create([
            'to' => $request->validated('to'),
            'content' => $request->validated('content'),
            'status' => 'pending',
        ]);

        SendSmsJob::dispatch($message->id);

        return response()->json([
            'id' => $message->id,
            'status' => $message->status,
            'to' => $message->to,
            'content' => $message->content,
        ], 201);
    }
}
