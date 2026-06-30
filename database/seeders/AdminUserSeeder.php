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
            ['email' => 'jorembelen@gmail.com'],
            [
                'name'         => 'Jorem Belen',
                'username'     => 'jorem.belen',
                'password'     => Hash::make('password'),
                // Replace with your real phone number before first login.
                'phone_number' => '+966509740359',
            ]
        );
        User::updateOrCreate(
            ['email' => 'test@user.com'],
            [
                'name'         => 'Test User',
                'username'     => 'user',
                'password'     => Hash::make('password'),
                // Replace with your real phone number before first login.
                'phone_number' => '+639215275260',
            ]
        );
    }
}