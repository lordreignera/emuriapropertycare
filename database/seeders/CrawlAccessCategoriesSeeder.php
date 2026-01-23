<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrawlAccessCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('crawl_access_categories')->insert([
            [
                'category_code' => 'full_basement',
                'category_name' => 'No crawl / full basement',
                'score_points' => 0,
                'description' => 'Easy access to plumbing systems',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'crawl_clearance',
                'category_name' => 'Crawl w/ clearance & lighting',
                'score_points' => 1,
                'description' => 'Adequate crawl space conditions',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'low_clearance',
                'category_name' => 'Low-clearance crawl (<3 ft)',
                'score_points' => 2,
                'description' => 'Difficult access due to low height',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'damp_crawl',
                'category_name' => 'Damp / poorly ventilated crawl',
                'score_points' => 3,
                'description' => 'Poor conditions requiring special precautions',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_code' => 'hazardous_crawl',
                'category_name' => 'Hazardous crawl (mold/pests/structural)',
                'score_points' => 4,
                'description' => 'Dangerous access requiring remediation',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
