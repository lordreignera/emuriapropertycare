<?php

/**
 * Script to generate all CPI pricing system views
 * Run: php generate-cpi-views.php
 */

$viewDefinitions = [
    'property-types' => [
        'title' => 'Property Types',
        'model' => 'PropertyType',
        'columns' => [
            ['name', 'Name', 'string'],
            ['slug', 'Slug', 'code'],
            ['uses_residential_pricing', 'Residential', 'boolean'],
            ['uses_commercial_pricing', 'Commercial', 'boolean'],
        ],
        'form_fields' => [
            ['name', 'text', 'Name', true],
            ['slug', 'text', 'Slug', true],
            ['description', 'textarea', 'Description', false],
            ['uses_residential_pricing', 'checkbox', 'Uses Residential Pricing', false],
            ['uses_commercial_pricing', 'checkbox', 'Uses Commercial Pricing', false],
        ],
    ],
    'cpi-multipliers' => [
        'title' => 'CPI Multipliers',
        'model' => 'CpiMultiplier',
        'relation' => 'cpiBand',
        'columns' => [
            ['cpiBand.band_name', 'CPI Band', 'relation'],
            ['multiplier_value', 'Multiplier', 'decimal'],
        ],
        'form_fields' => [
            ['cpi_band_range_id', 'select', 'CPI Band', true, 'cpiBands'],
            ['multiplier_value', 'number', 'Multiplier Value', true, null, '0.01'],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'cpi-domains' => [
        'title' => 'CPI Domains',
        'model' => 'CpiDomain',
        'columns' => [
            ['name', 'Name', 'string'],
            ['slug', 'Slug', 'code'],
            ['max_points', 'Max Points', 'badge'],
        ],
        'form_fields' => [
            ['name', 'text', 'Name', true],
            ['slug', 'text', 'Slug', true],
            ['description', 'textarea', 'Description', false],
            ['max_points', 'number', 'Maximum Points', true],
        ],
    ],
    'supply-materials' => [
        'title' => 'Supply Line Materials',
        'model' => 'SupplyMaterial',
        'relation' => 'cpiDomain',
        'columns' => [
            ['material_name', 'Material', 'string'],
            ['cpiDomain.name', 'Domain', 'relation'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['material_name', 'text', 'Material Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'age-brackets' => [
        'title' => 'Age Brackets',
        'model' => 'AgeBracket',
        'relation' => 'cpiDomain',
        'columns' => [
            ['bracket_name', 'Bracket', 'string'],
            ['age_range', 'Age Range', 'custom'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['bracket_name', 'text', 'Bracket Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['min_age', 'number', 'Minimum Age (years)', true],
            ['max_age', 'number', 'Maximum Age (years)', false],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'containment-categories' => [
        'title' => 'Containment Categories',
        'model' => 'ContainmentCategory',
        'relation' => 'cpiDomain',
        'columns' => [
            ['category_name', 'Category', 'string'],
            ['cpiDomain.name', 'Domain', 'relation'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['category_name', 'text', 'Category Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'crawl-access' => [
        'title' => 'Crawl Space Access',
        'model' => 'CrawlAccess',
        'relation' => 'cpiDomain',
        'columns' => [
            ['category_name', 'Category', 'string'],
            ['cpiDomain.name', 'Domain', 'relation'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['category_name', 'text', 'Category Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'roof-access' => [
        'title' => 'Roof Access',
        'model' => 'RoofAccess',
        'relation' => 'cpiDomain',
        'columns' => [
            ['category_name', 'Category', 'string'],
            ['cpiDomain.name', 'Domain', 'relation'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['category_name', 'text', 'Category Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'equipment-requirements' => [
        'title' => 'Equipment Requirements',
        'model' => 'EquipmentRequirement',
        'relation' => 'cpiDomain',
        'columns' => [
            ['equipment_name', 'Equipment', 'string'],
            ['cpiDomain.name', 'Domain', 'relation'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['equipment_name', 'text', 'Equipment Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'complexity-categories' => [
        'title' => 'Complexity Categories',
        'model' => 'ComplexityCategory',
        'relation' => 'cpiDomain',
        'columns' => [
            ['category_name', 'Category', 'string'],
            ['cpiDomain.name', 'Domain', 'relation'],
            ['score_value', 'Score', 'badge'],
        ],
        'form_fields' => [
            ['category_name', 'text', 'Category Name', true],
            ['cpi_domain_id', 'select', 'CPI Domain', true, 'domains'],
            ['score_value', 'number', 'Score Value', true],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'residential-tiers' => [
        'title' => 'Residential Size Tiers',
        'model' => 'ResidentialTier',
        'columns' => [
            ['tier_name', 'Tier', 'string'],
            ['unit_range', 'Unit Range', 'custom'],
            ['size_factor', 'Size Factor', 'decimal'],
        ],
        'form_fields' => [
            ['tier_name', 'text', 'Tier Name', true],
            ['min_units', 'number', 'Minimum Units', true],
            ['max_units', 'number', 'Maximum Units', false],
            ['size_factor', 'number', 'Size Factor', true, null, '0.01'],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'commercial-settings' => [
        'title' => 'Commercial Size Settings',
        'model' => 'CommercialSetting',
        'columns' => [
            ['setting_name', 'Setting', 'string'],
            ['setting_key', 'Key', 'code'],
            ['setting_value', 'Value', 'decimal'],
        ],
        'form_fields' => [
            ['setting_name', 'text', 'Setting Name', true],
            ['setting_key', 'text', 'Setting Key', true],
            ['setting_value', 'number', 'Setting Value', true, null, '0.01'],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'mixed-use-settings' => [
        'title' => 'Mixed-Use Settings',
        'model' => 'MixedUseSetting',
        'columns' => [
            ['setting_name', 'Setting', 'string'],
            ['setting_key', 'Key', 'code'],
            ['setting_value', 'Value', 'decimal'],
        ],
        'form_fields' => [
            ['setting_name', 'text', 'Setting Name', true],
            ['setting_key', 'text', 'Setting Key', true],
            ['setting_value', 'number', 'Setting Value', true, null, '0.01'],
            ['description', 'textarea', 'Description', false],
        ],
    ],
    'pricing-config' => [
        'title' => 'Pricing System Configuration',
        'model' => 'PricingConfig',
        'columns' => [
            ['config_key', 'Config Key', 'code'],
            ['config_value', 'Value', 'string'],
            ['value_type', 'Type', 'badge'],
        ],
        'form_fields' => [
            ['config_key', 'text', 'Configuration Key', true],
            ['config_value', 'text', 'Configuration Value', true],
            ['value_type', 'select_enum', 'Value Type', true, ['string', 'integer', 'decimal', 'boolean']],
            ['description', 'textarea', 'Description', false],
        ],
    ],
];

echo "CPI Pricing System View Definitions loaded.\n";
echo "Total tables: " . count($viewDefinitions) . "\n";
echo "\nTo generate views, integrate this with your view generation logic.\n";
