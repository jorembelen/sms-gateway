<?php

use App\Http\Middleware\AdminAuth;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\VerifyOtp;
use App\Livewire\Admin\BlastSms;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\QueueMonitor;
use App\Livewire\Admin\Devices;
use App\Livewire\Admin\FailedMessages;
use App\Livewire\Admin\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin login / OTP (unauthenticated)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/otp', VerifyOtp::class)->name('otp');
    Route::post('/logout', function (Request $request) {
        auth()->logout();
        $request->session()->forget('admin_otp_pending');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('logout');
});

// Admin panel (fully authenticated via Auth guard)
Route::prefix('admin')->name('admin.')->middleware(AdminAuth::class)->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/messages', Messages::class)->name('messages');
    Route::get('/devices', Devices::class)->name('devices');
    Route::get('/failed', FailedMessages::class)->name('failed');
    Route::get('/blast', BlastSms::class)->name('blast');
    Route::get('/queue', QueueMonitor::class)->name('queue');
});
