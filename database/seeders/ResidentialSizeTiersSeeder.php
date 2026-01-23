<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResidentialSizeTiersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('residential_size_tiers')->insert([
            [
                'tier_name' => '1-5 units',
                'min_units' => 1,
                'max_units' => 5,
                'size_factor' => 1.00,
                'description' => 'Small residential properties',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier_name' => '6-20 units',
                'min_units' => 6,
                'max_units' => 20,
                'size_factor' => 1.25,
                'description' => 'Medium residential properties',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier_name' => '21-50 units',
                'min_units' => 21,
                'max_units' => 50,
                'size_factor' => 1.50,
                'description' => 'Large residential properties',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier_name' => '51+ units',
                'min_units' => 51,
                'max_units' => null,
                'size_factor' => 1.75,
                'description' => 'Very large residential properties',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
