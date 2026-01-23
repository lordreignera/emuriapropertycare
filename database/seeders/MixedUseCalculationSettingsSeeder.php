<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MixedUseCalculationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('mixed_use_calculation_settings')->insert([
            [
                'setting_name' => 'default_commercial_weight',
                'setting_value' => 50.00,
                'description' => 'Default percentage of commercial area for mixed-use properties',
                'updated_at' => now(),
            ],
        ]);
    }
}
