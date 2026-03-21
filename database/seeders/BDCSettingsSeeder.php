<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BDCSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        $settings = [
            [
                'setting_key' => 'loaded_hourly_rate',
                'setting_label' => 'Loaded Hourly Rate',
                'setting_description' => 'Full loaded hourly rate for technicians including wages, benefits, training, etc.',
                'setting_value' => 165.00,
                'unit' => '$/hr',
                'setting_type' => 'rate',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'infrastructure_percentage',
                'setting_label' => 'Infrastructure % of Labour',
                'setting_description' => 'Infrastructure overhead as percentage of labour cost (vehicles, tools, equipment, facilities)',
                'setting_value' => 0.30,
                'unit' => '%',
                'setting_type' => 'percentage',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'administration_percentage',
                'setting_label' => 'Administration % of Labour',
                'setting_description' => 'Administrative overhead as percentage of labour cost (office, software, management)',
                'setting_value' => 0.12,
                'unit' => '%',
                'setting_type' => 'percentage',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'setting_key'         => 'cpi_weight',
                'setting_label'       => 'CPI Weight (for ASI)',
                'setting_description' => 'Weighting factor (0–1) applied to CPI when computing the Asset Stability Index (ASI). Default 60%.',
                'setting_value'       => 0.60,
                'unit'                => 'coefficient',
                'setting_type'        => 'percentage',
                'is_active'           => true,
                'sort_order'          => 4,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'setting_key'         => 'tus_weight',
                'setting_label'       => 'TUS Weight (for ASI)',
                'setting_description' => 'Weighting factor (0–1) applied to TUS when computing the Asset Stability Index (ASI). Default 40%.',
                'setting_value'       => 0.40,
                'unit'                => 'coefficient',
                'setting_type'        => 'percentage',
                'is_active'           => true,
                'sort_order'          => 5,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'setting_key'         => 'tus_input_default',
                'setting_label'       => 'TUS Default Input (0–100)',
                'setting_description' => 'Default Tenant Underwriting Score when none is entered per inspection.',
                'setting_value'       => 75.0,
                'unit'                => 'score',
                'setting_type'        => 'count',
                'is_active'           => true,
                'sort_order'          => 6,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],

            // Legacy defaults kept for reference/fallback only.
            // These are now set per inspection (PHAR form), not global settings.
            [
                'setting_key' => 'visits_per_year',
                'setting_label' => 'Visits per Year (Per Inspection)',
                'setting_description' => 'Legacy default only. Visits per year is now set per property inspection in PHAR form.',
                'setting_value' => 8.00,
                'unit' => 'visits',
                'setting_type' => 'count',
                'is_active' => false,
                'sort_order' => 98,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'setting_key' => 'hours_per_visit',
                'setting_label' => 'Hours per Visit (Per Inspection)',
                'setting_description' => 'Legacy default only. Hours per visit is now set per property inspection in PHAR form.',
                'setting_value' => 4.50,
                'unit' => 'hours',
                'setting_type' => 'hours',
                'is_active' => false,
                'sort_order' => 99,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('bdc_settings')->upsert(
            $settings,
            ['setting_key'],
            ['setting_label', 'setting_description', 'setting_value', 'unit', 'setting_type', 'is_active', 'sort_order', 'updated_at']
        );
    }
}
