<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // Active devices with messages in every status
        // $activeDevices = Device::factory(3)->active()->create();

        // foreach ($activeDevices as $device) {
        //     Message::factory(3)->sent()->create(['device_id' => $device->id]);
        //     Message::factory(2)->delivered()->create(['device_id' => $device->id]);
        //     Message::factory(1)->pending()->create(['device_id' => $device->id]);
        //     Message::factory(1)->failed()->create(['device_id' => $device->id]);
        // }

        // // Inactive devices with failed/sent messages
        // $inactiveDevices = Device::factory(2)->inactive()->create();

        // foreach ($inactiveDevices as $device) {
        //     Message::factory(2)->failed()->create(['device_id' => $device->id]);
        //     Message::factory(1)->sent()->create(['device_id' => $device->id]);
        // }

        // // Orphan messages (no device assigned)
        // Message::factory(5)->pending()->withoutDevice()->create();
        // Message::factory(2)->failed()->withoutDevice()->create();
    }
}