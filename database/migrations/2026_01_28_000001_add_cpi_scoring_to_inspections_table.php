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
            
            // Weather & Overview
            $table->string('weather_conditions')->nullable()->after('scheduled_date');
            
            // Findings-based CPI output
            $table->integer('cpi_total_score')->default(0)->after('weather_conditions');
            
            // Size Factors (Calculated & Snapshot at inspection time)
            $table->decimal('residential_size_factor', 4, 2)->nullable()->after('cpi_total_score');
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
                
                // Weather
                'weather_conditions',
                
                // CPI outputs
                'cpi_total_score',
                
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
