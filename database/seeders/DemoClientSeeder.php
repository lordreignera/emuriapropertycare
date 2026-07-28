<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoClientSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'client@emuria.com'],
            [
                'name' => 'Demo Client',
                'account_type' => 'client',
                'requires_subscription' => false,
                'password' => Hash::make('P@ssw0rd123!'),
                'email_verified_at' => now(),
            ]
        );

        $clientRole = Role::where('name', 'Client')->first();
        if ($clientRole) {
            $user->syncRoles([$clientRole]);
        }

        Client::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'phone' => '0780000000',
                'address' => 'Makerere Hill Road, Kampala',
                'city' => 'Kampala',
                'province' => 'Central',
                'postal_code' => '00000',
                'country' => 'Uganda',
                'registered_at' => now(),
                'account_status' => 'active',
                'email_notifications' => true,
                'sms_notifications' => false,
                'preferred_contact_method' => 'email',
            ]
        );

        $this->command->info('Demo client seeded: client@emuria.com / P@ssw0rd123!');
    }
}
