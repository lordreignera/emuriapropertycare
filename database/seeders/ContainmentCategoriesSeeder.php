<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContainmentCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('containment_categories')->insert([
            [
                'category_code' => 'accessible_isolation',
                'category_name' => 'Accessible isolation',
                'score_points' => 0,
                'description' => 'Quick damage containment with accessible shutoffs',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'partial_isolation',
                'category_name' => 'Partial isolation',
                'score_points' => 1,
                'description' => 'Some containment capability',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'poor_isolation',
                'category_name' => 'Poor isolation',
                'score_points' => 2,
                'description' => 'Limited containment options',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'no_isolation',
                'category_name' => 'No isolation',
                'score_points' => 3,
                'description' => 'Cannot isolate areas - high damage risk',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
