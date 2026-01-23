<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CpiMultipliersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cpi_multipliers')->insert([
            [
                'band_code' => 'CPI-0',
                'multiplier' => 1.00,
                'description' => 'Base price - excellent condition',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-1',
                'multiplier' => 1.08,
                'description' => '+8% - minor maintenance needs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-2',
                'multiplier' => 1.18,
                'description' => '+18% - moderate maintenance needs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-3',
                'multiplier' => 1.35,
                'description' => '+35% - significant maintenance needs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'band_code' => 'CPI-4',
                'multiplier' => 1.55,
                'description' => '+55% - critical maintenance needs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
