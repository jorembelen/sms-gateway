<?php

namespace App\Livewire\Admin;

use App\Models\Message;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Messages extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $query = Message::with('device')->latest();

        if ($this->search !== '') {
            $query->where('to', 'like', "%{$this->search}%");
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return view('livewire.admin.messages', [
            'messages'  => $query->paginate(25),
            'statuses'  => ['pending', 'sent', 'delivered', 'failed'],
        ])->layout('layouts.admin', ['pageTitle' => 'Messages']);
    }
}
