<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use Livewire\Component;
use Livewire\WithPagination;

class Devices extends Component
{
    use WithPagination;

    public function deleteDevice(int $id): void
    {
        Device::findOrFail($id)->delete();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.devices', [
            'devices' => Device::withCount('messages')->latest()->paginate(25),
        ])->layout('layouts.admin', ['pageTitle' => 'Devices']);
    }
}
