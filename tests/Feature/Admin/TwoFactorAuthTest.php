<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendSmsJob;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\VerifyOtp;
use App\Models\Message;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_EMAIL = 'admin@test.local';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email'        => self::ADMIN_EMAIL,
            'password'     => Hash::make('correct-password'),
            'phone_number' => '+639123456789',
        ]);

        // Clear any rate limiter state carried between tests.
        RateLimiter::clear("otp_send:{$this->admin->id}");
    }

    // -----------------------------------------------------------------------
    // Helper: run step 1 and return the plain OTP code from the queued message
    // -----------------------------------------------------------------------
    private function completeStep1(): string
    {
        Queue::fake();

        Livewire::test(Login::class)
            ->set('email', self::ADMIN_EMAIL)
            ->set('password', 'correct-password')
            ->call('submit');

        $message = Message::latest()->first();
        preg_match('/Your admin login code is: (\d{6})/', $message->content, $m);

        return $m[1];
    }

    // -----------------------------------------------------------------------
    // 1. Full happy path: valid password + valid OTP → logged in
    // -----------------------------------------------------------------------

    public function test_valid_password_and_valid_otp_logs_in_and_redirects_to_dashboard(): void
    {
        $plainCode = $this->completeStep1();

        $this->assertNotNull(session('admin_otp_pending'));

        $this->withSession(['admin_otp_pending' => $this->admin->id]);

        Livewire::test(VerifyOtp::class)
            ->set('code', $plainCode)
            ->call('submit')
            ->assertRedirect(route('admin.dashboard'));

        $otp = OtpCode::where('user_id', $this->admin->id)->latest()->first();
        $this->assertNotNull($otp->consumed_at);
    }

    // -----------------------------------------------------------------------
    // 2. Valid password + wrong OTP → rejected, attempts counter increments
    // -----------------------------------------------------------------------

    public function test_wrong_otp_is_rejected_and_increments_attempts(): void
    {
        $this->completeStep1();

        $otp = OtpCode::where('user_id', $this->admin->id)->latest()->first();
        $this->assertEquals(0, $otp->attempts);

        $this->withSession(['admin_otp_pending' => $this->admin->id]);

        Livewire::test(VerifyOtp::class)
            ->set('code', '000000')
            ->call('submit')
            ->assertSet('errorMessage', fn ($v) => str_contains($v, 'Incorrect'));

        $this->assertEquals(1, $otp->fresh()->attempts);
    }

    // -----------------------------------------------------------------------
    // 3. Expired OTP → rejected with clear "expired" message
    // -----------------------------------------------------------------------

    public function test_expired_otp_is_rejected_with_expired_message(): void
    {
        $this->completeStep1();

        OtpCode::where('user_id', $this->admin->id)->update(['expires_at' => now()->subMinute()]);

        $this->withSession(['admin_otp_pending' => $this->admin->id]);

        Livewire::test(VerifyOtp::class)
            ->set('code', '123456')
            ->call('submit')
            ->assertSet('errorMessage', fn ($v) => str_contains($v, 'expired'));
    }

    // -----------------------------------------------------------------------
    // 4. Wrong password → rejected before any OTP or SMS is generated
    // -----------------------------------------------------------------------

    public function test_wrong_password_rejects_without_creating_message_or_job(): void
    {
        Queue::fake();

        Livewire::test(Login::class)
            ->set('email', self::ADMIN_EMAIL)
            ->set('password', 'wrong-password')
            ->call('submit')
            ->assertHasErrors('form');

        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('otp_codes', 0);
        Queue::assertNothingPushed();
    }

    // -----------------------------------------------------------------------
    // 5. Five failed OTP attempts → locked out
    // -----------------------------------------------------------------------

    public function test_five_failed_otp_attempts_lock_out_the_user(): void
    {
        $this->completeStep1();

        // Seed attempts to 4 so next wrong guess triggers lockout.
        OtpCode::where('user_id', $this->admin->id)->update(['attempts' => 4]);

        $this->withSession(['admin_otp_pending' => $this->admin->id]);

        Livewire::test(VerifyOtp::class)
            ->set('code', '000000')
            ->call('submit')
            ->assertSet('errorMessage', fn ($v) => str_contains($v, 'Too many'));
    }

    // -----------------------------------------------------------------------
    // 6. OTP rate limiting — 4th send request within 10 minutes is blocked
    // -----------------------------------------------------------------------

    public function test_fourth_otp_send_request_within_window_is_rate_limited(): void
    {
        $key = "otp_send:{$this->admin->id}";
        RateLimiter::hit($key, 600);
        RateLimiter::hit($key, 600);
        RateLimiter::hit($key, 600);

        $service = new OtpService;
        $result  = $service->generateAndSend($this->admin);

        $this->assertFalse($result, 'Rate limiter should block the 4th OTP send.');
    }

    // -----------------------------------------------------------------------
    // 7. Direct /admin/dashboard access after step 1 only → redirected to login
    // -----------------------------------------------------------------------

    public function test_dashboard_is_inaccessible_after_only_completing_password_step(): void
    {
        $response = $this->withSession(['admin_otp_pending' => $this->admin->id])
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    // -----------------------------------------------------------------------
    // 8. OTP is stored hashed, not as plaintext
    // -----------------------------------------------------------------------

    public function test_otp_code_is_stored_hashed_not_in_plaintext(): void
    {
        $plainCode = $this->completeStep1();

        $otp = OtpCode::where('user_id', $this->admin->id)->first();
        $this->assertNotNull($otp);

        $this->assertNotEquals($plainCode, $otp->code, 'OTP was stored in plaintext — must be hashed.');
        $this->assertTrue(Hash::check($plainCode, $otp->code), 'Stored hash does not verify against plain code.');
    }

    // -----------------------------------------------------------------------
    // 9. Consumed OTP cannot be reused
    // -----------------------------------------------------------------------

    public function test_consumed_otp_cannot_be_used_again(): void
    {
        $plainCode = $this->completeStep1();

        // Mark the OTP as consumed directly (simulating a successful verification).
        OtpCode::where('user_id', $this->admin->id)->update(['consumed_at' => now()]);

        // Attempt to reuse the consumed code.
        $this->withSession(['admin_otp_pending' => $this->admin->id]);

        Livewire::test(VerifyOtp::class)
            ->set('code', $plainCode)
            ->call('submit')
            ->assertSet('errorMessage', fn ($v) => $v !== null && ! str_contains($v, 'dashboard'));

        $this->assertGuest('web');
    }

    // -----------------------------------------------------------------------
    // 10. Queue::fake() — jobs are pushed but NOT executed during tests
    // -----------------------------------------------------------------------

    public function test_send_sms_job_is_queued_but_not_actually_executed(): void
    {
        Queue::fake();

        Livewire::test(Login::class)
            ->set('email', self::ADMIN_EMAIL)
            ->set('password', 'correct-password')
            ->call('submit');

        Queue::assertPushed(SendSmsJob::class);
        $this->assertDatabaseHas('messages', ['to' => '+639123456789', 'status' => 'pending']);
    }
}
