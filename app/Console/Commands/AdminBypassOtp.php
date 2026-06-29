<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminBypassOtp extends Command
{
    protected $signature = 'admin:bypass-otp {--email= : Email of the admin user (defaults to first user)}';
    protected $description = 'Generate a bypass OTP code directly (break-glass: use when SMS device is offline)';

    public function handle(): int
    {
        $email = $this->option('email');

        $user = $email
            ? User::where('email', $email)->first()
            : User::first();

        if (! $user) {
            $this->error('No admin user found. Run db:seed or specify --email.');

            return self::FAILURE;
        }

        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->newLine();
        $this->line('  <fg=yellow;options=bold>⚠  BREAK-GLASS OTP — EMERGENCY USE ONLY</>');
        $this->newLine();
        $this->line("  User   : {$user->email}");
        $this->line("  Code   : <fg=green;options=bold>{$plainCode}</>");
        $this->line('  Valid  : 15 minutes');
        $this->line('  URL    : /admin/otp');
        $this->newLine();
        $this->warn('  Enter your password at /admin/login first, then enter this code at /admin/otp.');
        $this->warn('  This code bypasses SMS delivery — do not share it.');
        $this->newLine();

        return self::SUCCESS;
    }
}
