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
            CPIPricingSystemSeeder::class,
            // TierSeeder::class, // REMOVED: Tiers are now generated per client after inspection
        ]);
        
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('ℹ️  Note: Tiers are now dynamically generated per client based on inspection data.');
    }
}
