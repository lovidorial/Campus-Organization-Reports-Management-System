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
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'), // Default password
                'role' => 'admin',
            ]
        );

        // Additional test users — avoid duplicates by using firstOrCreate
        User::firstOrCreate(
            ['email' => 'paolo@gmail.com'],
            [
                'name' => 'paolo contist',
                'password' => Hash::make('paolo123'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'jade@gmail.com'],
            [
                'name' => 'jade Unciano',
                'password' => Hash::make('jade123'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'jums@gmail.com'],
            [
                'name' => 'adrian Villena',
                'password' => Hash::make('jumong'),
                'role' => 'user',
            ]
        );
    }
}