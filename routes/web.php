<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Middleware\AdminAuth;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Devices;
use App\Livewire\Admin\FailedMessages;
use App\Livewire\Admin\Messages;
use Illuminate\Support\Facades\Route;

// Admin login (unauthenticated)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Admin panel (authenticated)
Route::prefix('admin')->name('admin.')->middleware(AdminAuth::class)->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/messages', Messages::class)->name('messages');
    Route::get('/devices', Devices::class)->name('devices');
    Route::get('/failed', FailedMessages::class)->name('failed');
});