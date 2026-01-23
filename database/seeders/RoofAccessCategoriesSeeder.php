<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoofAccessCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roof_access_categories')->insert([
            [
                'category_code' => 'flat_low_pitch',
                'category_name' => 'Flat/low pitch (<4:12) safe access',
                'score_points' => 0,
                'description' => 'Easy and safe roof access',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'moderate_pitch',
                'category_name' => 'Moderate pitch (4:12–7:12)',
                'score_points' => 1,
                'description' => 'Moderate difficulty accessing roof',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'high_pitch',
                'category_name' => 'High pitch (>7:12)',
                'score_points' => 2,
                'description' => 'Steep roof requiring safety equipment',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'high_specialty',
                'category_name' => 'High pitch + brittle/specialty roofing',
                'score_points' => 3,
                'description' => 'Difficult access with fragile materials',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
