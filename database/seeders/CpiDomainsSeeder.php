<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CpiDomainsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::table('cpi_scoring_factors')->truncate();
        DB::table('cpi_domains')->truncate();
        
        // Domain 1: System Design & Pressure
        $domain1Id = DB::table('cpi_domains')->insertGetId([
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
        ]);

        DB::table('cpi_scoring_factors')->insert([
            [
                'domain_id' => $domain1Id,
                'factor_code' => 'unit_shutoffs',
                'factor_label' => 'Unit-level water shut-offs present? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 3,
                'calculation_rule' => json_encode(['no' => 3, 'yes' => 0]),
                'help_text' => 'If No, cascading risk across units.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain1Id,
                'factor_code' => 'shared_risers',
                'factor_label' => 'Shared risers impacting multiple units? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 2,
                'calculation_rule' => json_encode(['yes' => 2, 'no' => 0]),
                'help_text' => 'Vertical dependency elevates severity.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain1Id,
                'factor_code' => 'static_pressure',
                'factor_label' => 'Static water pressure (PSI)',
                'field_type' => 'numeric',
                'lookup_table' => null,
                'max_points' => 2,
                'calculation_rule' => json_encode(['range' => [120, 999], 'points' => 2]),
                'help_text' => 'Score ≥2 if >80 PSI.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain1Id,
                'factor_code' => 'isolation_zones',
                'factor_label' => 'Isolation zones present? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 2,
                'calculation_rule' => json_encode(['no' => 2, 'yes' => 0]),
                'help_text' => 'If No, harder containment.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Domain 2: Material Risk (Supply Lines)
        $domain2Id = DB::table('cpi_domains')->insertGetId([
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
        ]);

        DB::table('cpi_scoring_factors')->insert([
            [
                'domain_id' => $domain2Id,
                'factor_code' => 'supply_material',
                'factor_label' => 'Primary supply-line material (select from Lookups list)',
                'field_type' => 'lookup',
                'lookup_table' => 'supply_line_materials',
                'max_points' => 4,
                'calculation_rule' => json_encode(['source' => 'lookup_score']),
                'help_text' => 'Poly-B automatically drives high CPI.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain2Id,
                'factor_code' => 'drain_material_unknown',
                'factor_label' => 'Drain/Waste material unknown? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 1,
                'calculation_rule' => json_encode(['yes' => 1, 'no' => 0]),
                'help_text' => 'Optional +1 uncertainty modifier for drains/waste.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Domain 3: Age & Lifecycle (Use higher of Building vs System)
        $domain3Id = DB::table('cpi_domains')->insertGetId([
            'domain_number' => 3,
            'domain_name' => 'Age & Lifecycle (Use higher of Building vs System)',
            'domain_code' => 'age',
            'max_possible_points' => 5,
            'description' => 'Building and system age with documentation - uses higher score',
            'calculation_method' => 'max',
            'is_active' => true,
            'sort_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cpi_scoring_factors')->insert([
            [
                'domain_id' => $domain3Id,
                'factor_code' => 'building_age',
                'factor_label' => 'Building age (years)',
                'field_type' => 'numeric',
                'lookup_table' => 'age_brackets',
                'max_points' => 4,
                'calculation_rule' => json_encode(['lookup_by_age' => true]),
                'help_text' => 'Score bracketed by age.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain3Id,
                'factor_code' => 'fixture_age',
                'factor_label' => 'Fixture/system age (years)',
                'field_type' => 'numeric',
                'lookup_table' => 'age_brackets',
                'max_points' => 4,
                'calculation_rule' => json_encode(['lookup_by_age' => true]),
                'help_text' => 'Valves, heaters, pumps, key fixtures.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain3Id,
                'factor_code' => 'systems_documented',
                'factor_label' => 'Systems documented? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 1,
                'calculation_rule' => json_encode(['no' => 1, 'yes' => 0]),
                'help_text' => '+1 if No (uncertainty modifier).',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Domain 4: Access & Containment
        $domain4Id = DB::table('cpi_domains')->insertGetId([
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
        ]);

        DB::table('cpi_scoring_factors')->insert([
            [
                'domain_id' => $domain4Id,
                'factor_code' => 'containment_category',
                'factor_label' => 'Containment category (use Lookups list)',
                'field_type' => 'lookup',
                'lookup_table' => 'containment_categories',
                'max_points' => 3,
                'calculation_rule' => json_encode(['source' => 'lookup_score']),
                'help_text' => 'How quickly damage can be isolated.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Domain 5: Accessibility & Safety (MAX of sub-scores; capped at 4)
        $domain5Id = DB::table('cpi_domains')->insertGetId([
            'domain_number' => 5,
            'domain_name' => 'Accessibility & Safety (MAX of sub-scores; capped at 4)',
            'domain_code' => 'accessibility',
            'max_possible_points' => 4,
            'description' => 'Access difficulty and safety concerns - takes worst case, capped at 4',
            'calculation_method' => 'max',
            'is_active' => true,
            'sort_order' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cpi_scoring_factors')->insert([
            [
                'domain_id' => $domain5Id,
                'factor_code' => 'crawl_access',
                'factor_label' => 'Crawl/Confined access category (Lookups list)',
                'field_type' => 'lookup',
                'lookup_table' => 'crawl_access_categories',
                'max_points' => 4,
                'calculation_rule' => json_encode(['source' => 'lookup_score']),
                'help_text' => 'Takes the worst-case access risk and caps at 4.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain5Id,
                'factor_code' => 'roof_access',
                'factor_label' => 'Roof access category (Lookups list)',
                'field_type' => 'lookup',
                'lookup_table' => 'roof_access_categories',
                'max_points' => 4,
                'calculation_rule' => json_encode(['source' => 'lookup_score']),
                'help_text' => 'Takes the worst-case access risk and caps at 4.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain5Id,
                'factor_code' => 'equipment_requirement',
                'factor_label' => 'Equipment requirement (Lookups list)',
                'field_type' => 'lookup',
                'lookup_table' => 'equipment_requirements',
                'max_points' => 4,
                'calculation_rule' => json_encode(['source' => 'lookup_score']),
                'help_text' => 'Takes the worst-case access risk and caps at 4.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'domain_id' => $domain5Id,
                'factor_code' => 'time_to_access',
                'factor_label' => 'Time to access critical systems (minutes)',
                'field_type' => 'numeric',
                'lookup_table' => null,
                'max_points' => 0,
                'calculation_rule' => json_encode(['informational' => true]),
                'help_text' => 'Informational only.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Domain 6: Operational Complexity
        $domain6Id = DB::table('cpi_domains')->insertGetId([
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
        ]);

        DB::table('cpi_scoring_factors')->insert([
            [
                'domain_id' => $domain6Id,
                'factor_code' => 'complexity_category',
                'factor_label' => 'Complexity category (Lookups list)',
                'field_type' => 'lookup',
                'lookup_table' => 'complexity_categories',
                'max_points' => 3,
                'calculation_rule' => json_encode(['source' => 'lookup_score']),
                'help_text' => 'Tenant density, mixed-use, business interruption exposure.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
