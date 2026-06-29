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
    public string $email = '';
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
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $this->email)->first();

        // Constant-time failure path — don't reveal whether email exists.
        if (! $user || ! Hash::check($this->password, $user->password)) {
            $this->addError('form', 'These credentials do not match our records.');

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
