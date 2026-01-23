<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplyLineMaterialsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('supply_line_materials')->insert([
            [
                'material_code' => 'copper',
                'material_name' => 'Copper',
                'score_points' => 0,
                'risk_level' => 'Low',
                'description' => 'Best quality - durable and reliable',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'material_code' => 'pex',
                'material_name' => 'PEX',
                'score_points' => 1,
                'risk_level' => 'Low',
                'description' => 'Modern flexible piping',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'material_code' => 'cpvc',
                'material_name' => 'CPVC',
                'score_points' => 2,
                'risk_level' => 'Medium',
                'description' => 'Plastic piping - moderate risk',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'material_code' => 'galvanized',
                'material_name' => 'Galvanized',
                'score_points' => 3,
                'risk_level' => 'High',
                'description' => 'Old metal pipes - prone to corrosion',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'material_code' => 'poly_b',
                'material_name' => 'Poly-B',
                'score_points' => 4,
                'risk_level' => 'Critical',
                'description' => 'High failure risk - should be replaced',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'material_code' => 'mixed',
                'material_name' => 'Mixed/Unknown',
                'score_points' => 2,
                'risk_level' => 'Medium',
                'description' => 'Multiple materials or unknown type',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
