<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricingSystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pricing_system_config')->insert([
            [
                'config_key' => 'cpi_system_enabled',
                'config_value' => 'true',
                'data_type' => 'boolean',
                'config_group' => 'pricing',
                'description' => 'Enable CPI-based pricing calculations',
                'is_public' => false,
                'updated_at' => now(),
            ],
            [
                'config_key' => 'inspection_fee_amount',
                'config_value' => '299.00',
                'data_type' => 'decimal',
                'config_group' => 'inspection',
                'description' => 'Standard inspection fee amount',
                'is_public' => true,
                'updated_at' => now(),
            ],
            [
                'config_key' => 'allow_mixed_use_properties',
                'config_value' => 'true',
                'data_type' => 'boolean',
                'config_group' => 'general',
                'description' => 'Allow registration of mixed-use properties',
                'is_public' => false,
                'updated_at' => now(),
            ],
            [
                'config_key' => 'default_currency',
                'config_value' => 'CAD',
                'data_type' => 'text',
                'config_group' => 'pricing',
                'description' => 'Default currency code',
                'is_public' => true,
                'updated_at' => now(),
            ],
            [
                'config_key' => 'cpi_recalculation_frequency',
                'config_value' => '12',
                'data_type' => 'integer',
                'config_group' => 'pricing',
                'description' => 'How often CPI should be recalculated (months)',
                'is_public' => false,
                'updated_at' => now(),
            ],
        ]);
    }
}
