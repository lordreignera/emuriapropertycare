<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReactiveCostAssumptionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('reactive_cost_assumptions')->insert([
            [
                'severity_level' => 'LOW',
                'typical_cost' => 500.00,
                'annual_probability' => 0.05,
                'claimable_fraction' => 0.10,
                'description' => 'Minor issues, quick fixes',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'severity_level' => 'MODERATE',
                'typical_cost' => 2500.00,
                'annual_probability' => 0.10,
                'claimable_fraction' => 0.25,
                'description' => 'Moderate damage, standard repairs',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'severity_level' => 'HIGH',
                'typical_cost' => 8000.00,
                'annual_probability' => 0.20,
                'claimable_fraction' => 0.45,
                'description' => 'Significant damage, extensive repairs',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'severity_level' => 'CRITICAL',
                'typical_cost' => 25000.00,
                'annual_probability' => 0.35,
                'claimable_fraction' => 0.60,
                'description' => 'Severe damage, major reconstruction',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
