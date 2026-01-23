<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommercialSizeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('commercial_size_settings')->insert([
            [
                'setting_name' => 'base_sqft_divisor',
                'setting_value' => 10000.00,
                'data_type' => 'decimal',
                'description' => 'Square footage divided by this value equals size factor (SizeFactor = MAX(1, SqFt / this value))',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_name' => 'min_factor',
                'setting_value' => 1.00,
                'data_type' => 'decimal',
                'description' => 'Minimum size factor for small commercial spaces (prevents factor < 1.0)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'setting_name' => 'max_factor',
                'setting_value' => null,
                'data_type' => 'decimal',
                'description' => 'Maximum size factor cap (null = no limit, if set: SizeFactor = MIN(calculated, cap))',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
