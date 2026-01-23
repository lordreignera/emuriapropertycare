<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgeBracketsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('age_brackets')->insert([
            [
                'bracket_name' => '0-10 years',
                'min_age' => 0,
                'max_age' => 10,
                'score_points' => 0,
                'description' => 'New construction or recently renovated',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bracket_name' => '11-25 years',
                'min_age' => 11,
                'max_age' => 25,
                'score_points' => 1,
                'description' => 'Relatively modern systems',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bracket_name' => '26-40 years',
                'min_age' => 26,
                'max_age' => 40,
                'score_points' => 2,
                'description' => 'Mid-life systems requiring attention',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bracket_name' => '41-60 years',
                'min_age' => 41,
                'max_age' => 60,
                'score_points' => 3,
                'description' => 'Aging systems nearing end of life',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bracket_name' => '61+ years',
                'min_age' => 61,
                'max_age' => null,
                'score_points' => 4,
                'description' => 'Very old systems requiring replacement',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
