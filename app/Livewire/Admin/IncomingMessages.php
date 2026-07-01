<?php

namespace App\Livewire\Admin;

use App\Models\IncomingMessage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IncomingMessages extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $filter = 'all'; // all | linked | unlinked

    public ?int $selectedId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function showDetail(int $id): void
    {
        $this->selectedId = $id;
    }

    public function closeDetail(): void
    {
        $this->selectedId = null;
    }

    public function render(): \Illuminate\View\View
    {
        $query = IncomingMessage::with(['device', 'outboundMessage'])->latest('received_at');

        if ($this->search !== '') {
            $query->where('sender', 'like', "%{$this->search}%");
        }

        if ($this->filter === 'linked') {
            $query->whereNotNull('outbound_message_id');
        } elseif ($this->filter === 'unlinked') {
            $query->whereNull('outbound_message_id');
        }

        $selectedMessage = $this->selectedId
            ? IncomingMessage::with(['device', 'outboundMessage'])->find($this->selectedId)
            : null;

        return view('livewire.admin.incoming-messages', [
            'messages'        => $query->paginate(25),
            'selectedMessage' => $selectedMessage,
        ])->layout('layouts.admin', ['pageTitle' => 'Incoming Messages']);
    }
}
