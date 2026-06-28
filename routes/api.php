<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.key')->group(function () {
    Route::post('/devices/register', [DeviceController::class, 'register']);
    Route::post('/devices/{device}/callback', [DeviceController::class, 'callback']);

    Route::post('/messages/send', [MessageController::class, 'send']);
});
