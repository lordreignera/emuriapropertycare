<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('property_types')->insert([
            [
                'type_code' => 'residential',
                'type_name' => 'Residential',
                'uses_unit_count' => true,
                'uses_square_footage' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type_code' => 'commercial',
                'type_name' => 'Commercial',
                'uses_unit_count' => false,
                'uses_square_footage' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type_code' => 'mixed_use',
                'type_name' => 'Mixed-Use',
                'uses_unit_count' => true,
                'uses_square_footage' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
