<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Roles and Permissions first
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            TierSeeder::class,
        ]);
        
        $this->command->info('✅ Database seeding completed successfully!');
    }
}
