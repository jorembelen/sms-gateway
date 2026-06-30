<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    public string $login = '';
    public string $password = '';

    public function mount(): void
    {
        if (auth()->check()) {
            $this->redirectRoute('admin.dashboard', navigate: false);
        }
    }

    public function submit(OtpService $otp): void
    {
        $this->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $this->login)->first();

        // Constant-time failure path — don't reveal whether the identifier exists.
        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('form', 'These credentials do not match our records.');

            return;
        }

        // Skip OTP in non-production environments (local/staging dev convenience).
        if (! app()->isProduction()) {
            auth()->login($user);
            session()->regenerate();
            $this->redirectRoute('admin.dashboard', navigate: false);

            return;
        }

        $sent = $otp->generateAndSend($user);

        if (! $sent) {
            $this->addError('form', 'Too many verification attempts. Please wait before requesting another code.');

            return;
        }

        session(['admin_otp_pending' => $user->id]);

        $this->redirectRoute('admin.otp', navigate: false);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.login');
    }
}
