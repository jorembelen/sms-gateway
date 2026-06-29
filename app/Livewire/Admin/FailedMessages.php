<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\Message;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class FailedMessages extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $query = Message::with('device')
            ->where('status', 'failed')
            ->latest();

        if ($this->search !== '') {
            $query->where('to', 'like', "%{$this->search}%");
        }

        $failedToday   = Message::where('status', 'failed')->whereDate('created_at', Carbon::today())->count();
        $offlineDevices = Device::where('status', 'inactive')->count();

        return view('livewire.admin.failed-messages', [
            'messages'       => $query->paginate(25),
            'failedToday'    => $failedToday,
            'offlineDevices' => $offlineDevices,
        ])->layout('layouts.admin', ['pageTitle' => 'Failed Messages & Alerts']);
    }
}
