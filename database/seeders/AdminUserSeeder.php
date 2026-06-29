<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@smsgateway.local'],
            [
                'name'         => 'Admin',
                'password'     => Hash::make('password'),
                // Replace with your real phone number before first login.
                'phone_number' => '+966509740359',
            ]
        );
    }
}