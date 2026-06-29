<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendSmsJob;
use App\Models\Device;
use App\Models\Message;
use App\Services\Fcm\FcmException;
use App\Services\Fcm\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendSmsJobTest extends TestCase
{
    use RefreshDatabase;

    private function fcmThatSucceeds(): FcmService
    {
        $mock = Mockery::mock(FcmService::class);
        $mock->shouldReceive('sendDataMessage')->once()->andReturnNull();

        return $mock;
    }

    private function fcmThatFails(bool $tokenInvalid): FcmService
    {
        $mock = Mockery::mock(FcmService::class);
        $mock->shouldReceive('sendDataMessage')
            ->once()
            ->andThrow(new FcmException('FCM error', $tokenInvalid));

        return $mock;
    }

    public function test_skips_silently_when_message_no_longer_exists(): void
    {
        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldNotReceive('sendDataMessage');

        (new SendSmsJob(99999))->handle($fcm);
    }

    public function test_skips_message_that_is_already_past_pending(): void
    {
        $message = Message::factory()->sent()->withoutDevice()->create();
        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldNotReceive('sendDataMessage');

        (new SendSmsJob($message->id))->handle($fcm);

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'sent']);
    }

    public function test_marks_message_failed_when_no_active_device_exists(): void
    {
        Device::factory()->inactive()->create();
        $message = Message::factory()->pending()->withoutDevice()->create();
        $fcm = Mockery::mock(FcmService::class);
        $fcm->shouldNotReceive('sendDataMessage');

        (new SendSmsJob($message->id))->handle($fcm);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'failed',
            'failure_reason' => 'no active device',
        ]);
    }

    public function test_assigns_device_and_leaves_message_pending_after_successful_fcm_send(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->withoutDevice()->create();

        (new SendSmsJob($message->id))->handle($this->fcmThatSucceeds());

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'pending',
            'device_id' => $device->id,
        ]);
    }

    public function test_uses_most_recently_seen_active_device(): void
    {
        $older = Device::factory()->active()->create(['last_seen_at' => now()->subHour()]);
        $newer = Device::factory()->active()->create(['last_seen_at' => now()->subMinute()]);
        $message = Message::factory()->pending()->withoutDevice()->create();

        (new SendSmsJob($message->id))->handle($this->fcmThatSucceeds());

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'device_id' => $newer->id]);
        $this->assertNotEquals($older->id, Message::find($message->id)->device_id);
    }

    public function test_marks_message_failed_and_deactivates_device_on_permanent_fcm_error(): void
    {
        $device = Device::factory()->active()->create();
        $message = Message::factory()->pending()->withoutDevice()->create();

        (new SendSmsJob($message->id))->handle($this->fcmThatFails(tokenInvalid: true));

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'failed']);
        $this->assertDatabaseHas('devices', ['id' => $device->id, 'status' => 'inactive']);
    }

    public function test_rethrows_transient_fcm_error_so_queue_can_retry(): void
    {
        Device::factory()->active()->create();
        $message = Message::factory()->pending()->withoutDevice()->create();

        $this->expectException(FcmException::class);

        (new SendSmsJob($message->id))->handle($this->fcmThatFails(tokenInvalid: false));
    }

    public function test_failed_hook_marks_pending_message_as_failed(): void
    {
        $message = Message::factory()->pending()->withoutDevice()->create();

        (new SendSmsJob($message->id))->failed(new \RuntimeException('Queue timeout'));

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'failed']);
        $this->assertStringContainsString(
            'FCM send failed',
            (string) Message::find($message->id)->failure_reason
        );
    }

    public function test_failed_hook_does_not_overwrite_already_resolved_message(): void
    {
        $message = Message::factory()->delivered()->withoutDevice()->create();

        (new SendSmsJob($message->id))->failed(new \RuntimeException('Queue timeout'));

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'delivered']);
    }
}
