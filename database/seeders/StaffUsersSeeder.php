<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StaffUsersSeeder extends Seeder
{
    public function run(): void
    {
        $projectManager = User::updateOrCreate(
            ['email' => 'pm@emuria.com'],
            [
                'name' => 'Project Manager User',
                'password' => Hash::make('P@ssw0rd123!'),
                'email_verified_at' => now(),
            ]
        );

        $pmRole = Role::where('name', 'Project Manager')->first();
        if ($pmRole) {
            $projectManager->syncRoles([$pmRole]);
        }

        $inspector = User::updateOrCreate(
            ['email' => 'inspector@emuria.com'],
            [
                'name' => 'Inspector User',
                'password' => Hash::make('P@ssw0rd123!'),
                'email_verified_at' => now(),
            ]
        );

        $inspectorRole = Role::where('name', 'Inspector')->first();
        if ($inspectorRole) {
            $inspector->syncRoles([$inspectorRole]);
        }

        // Technician
        $technician = User::updateOrCreate(
            ['email' => 'technician@emuria.com'],
            [
                'name' => 'Technician User',
                'password' => Hash::make('P@ssw0rd123!'),
                'email_verified_at' => now(),
            ]
        );

        $techRole = Role::where('name', 'Technician')->first();
        if ($techRole) {
            $technician->syncRoles([$techRole]);
        }

        // Store Manager
        $storeManager = User::updateOrCreate(
            ['email' => 'storemanager@emuria.com'],
            [
                'name' => 'Store Manager User',
                'password' => Hash::make('P@ssw0rd123!'),
                'email_verified_at' => now(),
            ]
        );

        $storeManagerRole = Role::where('name', 'Store Manager')->first();
        if ($storeManagerRole) {
            $storeManager->syncRoles([$storeManagerRole]);
        }

        $this->command->info('Staff users seeded successfully!');
        $this->command->info('Project Manager: pm@emuria.com / P@ssw0rd123!');
        $this->command->info('Inspector: inspector@emuria.com / P@ssw0rd123!');
        $this->command->info('Technician: technician@emuria.com / P@ssw0rd123!');
        $this->command->info('Store Manager: storemanager@emuria.com / P@ssw0rd123!');
    }
}
