<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CpiBandRangesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cpi_band_ranges')->insert([
            [
                'band_code' => 'CPI-0',
                'band_name' => 'Excellent',
                'min_score' => 0,
                'max_score' => 2,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-1',
                'band_name' => 'Good',
                'min_score' => 3,
                'max_score' => 5,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-2',
                'band_name' => 'Fair',
                'min_score' => 6,
                'max_score' => 8,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-3',
                'band_name' => 'Poor',
                'min_score' => 9,
                'max_score' => 11,
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-4',
                'band_name' => 'Critical',
                'min_score' => 12,
                'max_score' => null,
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
