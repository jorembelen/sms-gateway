<?php

namespace App\Livewire\Admin;

use App\Jobs\SendSmsJob;
use App\Models\Device;
use App\Models\Message;
use Livewire\Component;

class BlastSms extends Component
{
    public string $content = '';
    public string $recipients = '';
    public ?int $deviceId = null;
    public bool $dispatched = false;
    public int $queued = 0;
    /** @var string[] */
    public array $skipped = [];

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'content'    => ['required', 'string', 'max:1000'],
            'recipients' => ['required', 'string'],
            'deviceId'   => ['nullable', 'integer', 'exists:devices,id'],
        ];
    }

    public function send(): void
    {
        $this->validate();

        $lines = array_filter(array_map('trim', explode("\n", $this->recipients)));

        $this->queued  = 0;
        $this->skipped = [];

        foreach ($lines as $line) {
            $number = preg_replace('/[\s\-\(\)]/', '', $line);

            if (! preg_match('/^\+?[0-9]{7,15}$/', $number)) {
                $this->skipped[] = $line;
                continue;
            }

            $message = Message::create([
                'to'        => $number,
                'content'   => trim($this->content),
                'status'    => 'pending',
                'device_id' => $this->deviceId,
            ]);

            SendSmsJob::dispatch($message->id);
            $this->queued++;
        }

        $this->dispatched = true;
        $this->content    = '';
        $this->recipients = '';
    }

    public function resetForm(): void
    {
        $this->dispatched = false;
        $this->queued     = 0;
        $this->skipped    = [];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.blast-sms', [
            'devices' => Device::where('status', 'active')->whereNotNull('fcm_token')->latest('last_seen_at')->get(),
        ])->layout('layouts.admin', ['pageTitle' => 'Blast SMS']);
    }
}
