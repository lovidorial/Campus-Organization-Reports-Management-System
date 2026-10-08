<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create Admin Account (or update if it already exists)
        User::updateOrCreate(
            ['email' => 'osdw@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'), // Default password
                'role' => 'admin',
            ]
        );

    }
}