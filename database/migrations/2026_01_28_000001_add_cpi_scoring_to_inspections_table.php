<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // Property & Owner Details (Snapshot at inspection time)
            $table->string('owner_name')->nullable()->after('property_id');
            $table->string('owner_email')->nullable()->after('owner_name');
            $table->string('owner_phone')->nullable()->after('owner_email');
            $table->string('property_code')->nullable()->after('owner_phone');
            $table->string('property_name')->nullable()->after('property_code');
            $table->text('property_address_snapshot')->nullable()->after('property_name');
            $table->enum('property_type_snapshot', ['residential', 'commercial', 'mixed_use'])->nullable()->after('property_address_snapshot');
            $table->integer('property_year_built')->nullable()->after('property_type_snapshot');
            $table->integer('residential_units_snapshot')->nullable()->after('property_year_built');
            $table->decimal('commercial_sqft_snapshot', 10, 2)->nullable()->after('residential_units_snapshot');
            $table->decimal('mixed_use_weight_snapshot', 5, 2)->nullable()->after('commercial_sqft_snapshot');
            
            // Service Package Selection (Snapshot at inspection time)
            $table->foreignId('service_package_id')->nullable()->after('mixed_use_weight_snapshot')->constrained('pricing_packages')->onDelete('restrict');
            $table->string('service_package_name')->nullable()->after('service_package_id'); // Snapshot
            $table->decimal('base_price_snapshot', 10, 2)->nullable()->after('service_package_name'); // Snapshot of base price at inspection time
            
            // Weather & Overview
            $table->string('weather_conditions')->nullable()->after('scheduled_date');
            
            // CPI Domain 1: System Design & Pressure (Max 7 pts)
            $table->enum('cpi_unit_shutoffs', ['yes', 'no'])->nullable()->after('summary');
            $table->enum('cpi_shared_risers', ['yes', 'no'])->nullable()->after('cpi_unit_shutoffs');
            $table->integer('cpi_static_pressure')->nullable()->after('cpi_shared_risers'); // PSI
            $table->enum('cpi_isolation_zones', ['yes', 'no'])->nullable()->after('cpi_static_pressure');
            $table->integer('domain_1_score')->default(0)->after('cpi_isolation_zones');
            $table->text('domain_1_notes')->nullable()->after('domain_1_score');
            
            // CPI Domain 2: Material Risk (Max 5 pts)
            $table->foreignId('cpi_supply_material_id')->nullable()->after('domain_1_notes')->constrained('supply_line_materials')->onDelete('restrict');
            $table->string('cpi_supply_material_name')->nullable()->after('cpi_supply_material_id'); // Snapshot
            $table->integer('cpi_supply_material_score')->default(0)->after('cpi_supply_material_name'); // Snapshot of score
            $table->enum('cpi_drain_material_unknown', ['yes', 'no'])->nullable()->after('cpi_supply_material_score');
            $table->integer('domain_2_score')->default(0)->after('cpi_drain_material_unknown');
            $table->text('domain_2_notes')->nullable()->after('domain_2_score');
            
            // CPI Domain 3: Age & Lifecycle (Max 5 pts)
            $table->integer('building_age_calculated')->nullable()->after('domain_2_notes'); // Auto-calculated
            $table->integer('cpi_fixture_age')->nullable()->after('building_age_calculated');
            $table->enum('cpi_systems_documented', ['yes', 'no'])->nullable()->after('cpi_fixture_age');
            $table->integer('cpi_age_score_harmonised')->default(0)->after('cpi_systems_documented');
            $table->integer('domain_3_score')->default(0)->after('cpi_age_score_harmonised');
            $table->text('domain_3_notes')->nullable()->after('domain_3_score');
            
            // CPI Domain 4: Access & Containment (Max 3 pts)
            $table->foreignId('cpi_containment_category_id')->nullable()->after('domain_3_notes')->constrained('containment_categories')->onDelete('restrict');
            $table->string('cpi_containment_category_name')->nullable()->after('cpi_containment_category_id'); // Snapshot
            $table->integer('cpi_containment_score')->default(0)->after('cpi_containment_category_name'); // Snapshot of score
            $table->integer('domain_4_score')->default(0)->after('cpi_containment_score');
            $table->text('domain_4_notes')->nullable()->after('domain_4_score');
            
            // CPI Domain 5: Accessibility & Safety (MAX of sub-scores, capped at 4)
            $table->foreignId('cpi_crawl_access_id')->nullable()->after('domain_4_notes')->constrained('crawl_access_categories')->onDelete('restrict');
            $table->string('cpi_crawl_access_name')->nullable()->after('cpi_crawl_access_id'); // Snapshot
            $table->integer('cpi_crawl_access_score')->default(0)->after('cpi_crawl_access_name'); // Snapshot
            
            $table->foreignId('cpi_roof_access_id')->nullable()->after('cpi_crawl_access_score')->constrained('roof_access_categories')->onDelete('restrict');
            $table->string('cpi_roof_access_name')->nullable()->after('cpi_roof_access_id'); // Snapshot
            $table->integer('cpi_roof_access_score')->default(0)->after('cpi_roof_access_name'); // Snapshot
            
            $table->foreignId('cpi_equipment_requirement_id')->nullable()->after('cpi_roof_access_score')->constrained('equipment_requirements')->onDelete('restrict');
            $table->string('cpi_equipment_requirement_name')->nullable()->after('cpi_equipment_requirement_id'); // Snapshot
            $table->integer('cpi_equipment_requirement_score')->default(0)->after('cpi_equipment_requirement_name'); // Snapshot
            
            $table->integer('cpi_time_to_access')->nullable()->after('cpi_equipment_requirement_score'); // Minutes
            $table->integer('cpi_accessibility_score_capped')->default(0)->after('cpi_time_to_access');
            $table->integer('domain_5_score')->default(0)->after('cpi_accessibility_score_capped');
            $table->text('domain_5_notes')->nullable()->after('domain_5_score');
            
            // CPI Domain 6: Operational Complexity (Max 3 pts)
            $table->foreignId('cpi_complexity_category_id')->nullable()->after('domain_5_notes')->constrained('complexity_categories')->onDelete('restrict');
            $table->string('cpi_complexity_category_name')->nullable()->after('cpi_complexity_category_id'); // Snapshot
            $table->integer('cpi_complexity_score')->default(0)->after('cpi_complexity_category_name'); // Snapshot
            $table->integer('domain_6_score')->default(0)->after('cpi_complexity_score');
            $table->text('domain_6_notes')->nullable()->after('domain_6_score');
            
            // CPI Outputs (Calculated & IMMUTABLE - Snapshot at inspection time)
            $table->integer('cpi_total_score')->default(0)->after('domain_6_notes');
            $table->string('cpi_band', 10)->nullable()->after('cpi_total_score'); // CPI-0, CPI-1, CPI-2, CPI-3, CPI-4
            $table->decimal('cpi_multiplier', 4, 2)->default(1.00)->after('cpi_band'); // 1.00, 1.08, 1.18, 1.35, 1.55
            
            // CPI Band Range Snapshot (at inspection time - prevents historical changes)
            $table->string('cpi_band_range_snapshot')->nullable()->after('cpi_multiplier'); // "9-11 points"
            $table->string('cpi_band_name_snapshot')->nullable()->after('cpi_band_range_snapshot'); // "Poor"
            
            // Size Factors (Calculated & Snapshot at inspection time)
            $table->decimal('residential_size_factor', 4, 2)->nullable()->after('cpi_band_name_snapshot');
            $table->decimal('commercial_size_factor', 4, 2)->nullable()->after('residential_size_factor');
            $table->decimal('harmonised_size_factor', 4, 2)->nullable()->after('commercial_size_factor');
            
            // Pricing Calculation (Snapshot at inspection time - IMMUTABLE)
            $table->decimal('final_monthly_cost', 10, 2)->nullable()->after('harmonised_size_factor');
            $table->decimal('final_annual_cost', 10, 2)->nullable()->after('final_monthly_cost');
            
            // Overall Assessment
            $table->enum('overall_condition', ['excellent', 'good', 'fair', 'poor', 'critical'])->nullable()->after('photos');
            $table->text('inspector_notes')->nullable()->after('overall_condition');
            $table->text('recommendations')->nullable()->after('inspector_notes');
            $table->text('risk_summary')->nullable()->after('recommendations');
            $table->text('photo_notes')->nullable()->after('risk_summary');
            
            // Indexes for CPI queries
            $table->index('cpi_total_score');
            $table->index('cpi_band');
            $table->index('service_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                // Property snapshots
                'owner_name', 'owner_email', 'owner_phone',
                'property_code', 'property_name', 'property_address_snapshot',
                'property_type_snapshot', 'property_year_built',
                'residential_units_snapshot', 'commercial_sqft_snapshot', 'mixed_use_weight_snapshot',
                
                // Service package snapshot
                'service_package_id', 'service_package_name', 'base_price_snapshot',
                
                // Weather
                'weather_conditions',
                
                // Domain 1
                'cpi_unit_shutoffs', 'cpi_shared_risers', 'cpi_static_pressure', 'cpi_isolation_zones',
                'domain_1_score', 'domain_1_notes',
                
                // Domain 2
                'cpi_supply_material_id', 'cpi_supply_material_name', 'cpi_supply_material_score',
                'cpi_drain_material_unknown', 'domain_2_score', 'domain_2_notes',
                
                // Domain 3
                'building_age_calculated', 'cpi_fixture_age', 'cpi_systems_documented',
                'cpi_age_score_harmonised', 'domain_3_score', 'domain_3_notes',
                
                // Domain 4
                'cpi_containment_category_id', 'cpi_containment_category_name', 'cpi_containment_score',
                'domain_4_score', 'domain_4_notes',
                
                // Domain 5
                'cpi_crawl_access_id', 'cpi_crawl_access_name', 'cpi_crawl_access_score',
                'cpi_roof_access_id', 'cpi_roof_access_name', 'cpi_roof_access_score',
                'cpi_equipment_requirement_id', 'cpi_equipment_requirement_name', 'cpi_equipment_requirement_score',
                'cpi_time_to_access', 'cpi_accessibility_score_capped',
                'domain_5_score', 'domain_5_notes',
                
                // Domain 6
                'cpi_complexity_category_id', 'cpi_complexity_category_name', 'cpi_complexity_score',
                'domain_6_score', 'domain_6_notes',
                
                // CPI outputs
                'cpi_total_score', 'cpi_band', 'cpi_multiplier',
                'cpi_band_range_snapshot', 'cpi_band_name_snapshot',
                
                // Size factors
                'residential_size_factor', 'commercial_size_factor', 'harmonised_size_factor',
                
                // Pricing
                'final_monthly_cost', 'final_annual_cost',
                
                // Assessment
                'overall_condition', 'inspector_notes', 'recommendations', 'risk_summary', 'photo_notes',
            ]);
        });
    }
};
