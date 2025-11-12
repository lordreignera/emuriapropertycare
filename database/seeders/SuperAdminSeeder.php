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
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@emuria.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'), // Change this in production!
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
        $this->command->info('Password: password');
        $this->command->warn('⚠️  IMPORTANT: Change the password in production!');
    }
}
