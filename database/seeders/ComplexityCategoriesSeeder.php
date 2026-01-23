<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplexityCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('complexity_categories')->insert([
            [
                'category_code' => 'low_density',
                'category_name' => 'Low density / simple',
                'score_points' => 0,
                'description' => 'Simple property with low operational complexity',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'medium_density',
                'category_name' => 'Medium density',
                'score_points' => 1,
                'description' => 'Moderate complexity and tenant density',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'high_density',
                'category_name' => 'High density',
                'score_points' => 2,
                'description' => 'High complexity with many units/tenants',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'business_critical',
                'category_name' => 'Business-critical',
                'score_points' => 3,
                'description' => 'Mixed-use or business operations dependent on system',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
