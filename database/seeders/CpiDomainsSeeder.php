<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CpiDomainsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cpi_domains')->insert([
            [
                'domain_number' => 1,
                'domain_name' => 'System Design & Pressure',
                'domain_code' => 'system_design',
                'max_possible_points' => 7,
                'description' => 'Overall system design, shutoffs, risers, and pressure regulation',
                'calculation_method' => 'sum',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_number' => 2,
                'domain_name' => 'Material Risk (Supply Lines)',
                'domain_code' => 'materials',
                'max_possible_points' => 5,
                'description' => 'Supply line material quality and condition',
                'calculation_method' => 'lookup',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_number' => 3,
                'domain_name' => 'Age & Lifecycle',
                'domain_code' => 'age',
                'max_possible_points' => 5,
                'description' => 'Building and system age with documentation',
                'calculation_method' => 'formula',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_number' => 4,
                'domain_name' => 'Access & Containment',
                'domain_code' => 'containment',
                'max_possible_points' => 3,
                'description' => 'Leak isolation and containment capabilities',
                'calculation_method' => 'lookup',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_number' => 5,
                'domain_name' => 'Accessibility & Safety',
                'domain_code' => 'accessibility',
                'max_possible_points' => 4,
                'description' => 'Access difficulty and safety concerns (takes worst case)',
                'calculation_method' => 'max',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_number' => 6,
                'domain_name' => 'Operational Complexity',
                'domain_code' => 'complexity',
                'max_possible_points' => 3,
                'description' => 'Tenant density and business criticality',
                'calculation_method' => 'lookup',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
