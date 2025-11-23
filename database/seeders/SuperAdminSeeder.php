<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin User
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@emuria.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('@dm1n2@25'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Super Admin role
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $superAdmin->assignRole($role);
        }

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@emuria.com');
        $this->command->info('Password: @dm1n2@25');
        $this->command->warn('🔒 Strong password set!');
    }
}
