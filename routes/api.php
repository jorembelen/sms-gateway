<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.key')->group(function () {
    Route::post('/devices/register', [DeviceController::class, 'register'])
        ->middleware('throttle:30,1');

    Route::post('/devices/{device}/callback', [DeviceController::class, 'callback'])
        ->middleware('throttle:120,1');

    Route::post('/messages/send', [MessageController::class, 'send'])
        ->middleware('throttle:60,1');
});
