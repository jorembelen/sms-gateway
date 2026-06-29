<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use App\Services\OtpService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class VerifyOtp extends Component
{
    public string $code = '';
    public ?string $errorMessage = null;
    public bool $rateLimited = false;
    public int $rateLimitSeconds = 0;

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirectRoute('admin.dashboard', navigate: false);

            return;
        }

        if (! session()->has('admin_otp_pending')) {
            $this->redirectRoute('admin.login', navigate: false);
        }
    }

    public function submit(OtpService $otp): void
    {
        $this->errorMessage = null;

        $this->validate(['code' => ['required', 'digits:6']]);

        $user = $this->resolveUser();
        if (! $user) {
            $this->redirectRoute('admin.login', navigate: false);

            return;
        }

        $result = $otp->verify($user, $this->code);

        match ($result) {
            'success' => $this->completeLogin($user),
            'expired' => $this->errorMessage = 'Your verification code has expired. Please request a new one.',
            'locked'  => $this->errorMessage = 'Too many failed attempts. Please request a new code.',
            'none'    => $this->errorMessage = 'No active verification code found. Please request a new one.',
            default   => $this->errorMessage = 'Incorrect code. Please try again.',
        };
    }

    public function resend(OtpService $otp): void
    {
        $this->errorMessage = null;
        $this->rateLimited = false;

        $user = $this->resolveUser();
        if (! $user) {
            $this->redirectRoute('admin.login', navigate: false);

            return;
        }

        $sent = $otp->generateAndSend($user);

        if (! $sent) {
            $this->rateLimited = true;
            $this->rateLimitSeconds = $otp->secondsUntilRateLimitClears($user);

            return;
        }

        session()->flash('status', 'A new code has been sent to your phone.');
    }

    private function completeLogin(User $user): void
    {
        session()->forget('admin_otp_pending');
        auth()->login($user);
        session()->regenerate();

        $this->redirectRoute('admin.dashboard', navigate: false);
    }

    private function resolveUser(): ?User
    {
        $userId = session('admin_otp_pending');

        return $userId ? User::find($userId) : null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.verify-otp');
    }
}
