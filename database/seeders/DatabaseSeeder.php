<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the AdminUserSeeder to create test users
        $this->call(AdminUserSeeder::class);

        // Load organization classification reference data for auto-detection
        $this->call(OrganizationClassificationSeeder::class);
    }
}
