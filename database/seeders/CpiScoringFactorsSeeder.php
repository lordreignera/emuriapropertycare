<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CpiScoringFactorsSeeder extends Seeder
{
    public function run(): void
    {
        $factors = [
            // Domain 1: System Design & Pressure
            [
                'domain_id' => 1,
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
            ],
            [
                'domain_id' => 1,
                'factor_code' => 'shared_risers',
                'factor_label' => 'Shared riser impacting multiple units? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 2,
                'calculation_rule' => json_encode(['yes' => 2, 'no' => 0]),
                'help_text' => 'Vertical dependency elevates severity.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'domain_id' => 1,
                'factor_code' => 'static_pressure',
                'factor_label' => 'Static water pressure (PSI)',
                'field_type' => 'numeric',
                'lookup_table' => null,
                'max_points' => 2,
                'calculation_rule' => json_encode(['range' => [120, 999], 'points' => 2]),
                'help_text' => 'Sense if > 60 PSI.',
                'is_required' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'domain_id' => 1,
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
            ],

            // Domain 2: Material Risk (Supply Lines)
            [
                'domain_id' => 2,
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
            ],
            [
                'domain_id' => 2,
                'factor_code' => 'drain_waste_unknown',
                'factor_label' => 'Drain/Waste material unknown? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 1,
                'calculation_rule' => json_encode(['yes' => 0, 'no' => 0]),
                'help_text' => 'Optional +1 uncertainty modifier for drains/waste.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],

            // Domain 3: Age & Lifecycle
            [
                'domain_id' => 3,
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
            ],
            [
                'domain_id' => 3,
                'factor_code' => 'fixture_age',
                'factor_label' => 'Fixture/system age (years)',
                'field_type' => 'numeric',
                'lookup_table' => null,
                'max_points' => 0,
                'calculation_rule' => json_encode(['informational' => true]),
                'help_text' => 'Valves, heaters, pumps; key fixtures.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'domain_id' => 3,
                'factor_code' => 'systems_documented',
                'factor_label' => 'Systems documented? (Yes/No)',
                'field_type' => 'yes_no',
                'lookup_table' => null,
                'max_points' => 0,
                'calculation_rule' => json_encode(['no' => 0, 'yes' => 0]),
                'help_text' => '+1 if No (uncertainty modifier).',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'domain_id' => 3,
                'factor_code' => 'age_score_harmonised',
                'factor_label' => 'Age Score (harmonised)',
                'field_type' => 'calculated',
                'lookup_table' => null,
                'max_points' => 4,
                'calculation_rule' => json_encode(['formula' => 'Higher of building vs fixtures, plus documentation modifier.']),
                'help_text' => 'Higher of building vs fixtures, plus documentation modifier.',
                'is_required' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],

            // Domain 4: Access & Containment
            [
                'domain_id' => 4,
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
            ],

            // Domain 5: Accessibility & Safety (MAX of sub-scores; capped at 4)
            [
                'domain_id' => 5,
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
            ],
            [
                'domain_id' => 5,
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
            ],
            [
                'domain_id' => 5,
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
            ],
            [
                'domain_id' => 5,
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
            ],

            // Domain 6: Operational Complexity
            [
                'domain_id' => 6,
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
            ],
        ];

        foreach ($factors as $factor) {
            $factor['created_at'] = now();
            $factor['updated_at'] = now();
            DB::table('cpi_scoring_factors')->insert($factor);
        }
    }
}
