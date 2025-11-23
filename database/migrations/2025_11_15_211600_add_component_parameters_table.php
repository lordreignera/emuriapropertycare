<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create component_parameters table (parameters belong to components)
        Schema::create('component_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('product_components')->onDelete('cascade');
            $table->string('parameter_name'); // e.g., "Labor Hours", "Material Quantity", "Square Footage"
            $table->text('description')->nullable();
            $table->enum('value_type', [
                'numeric',      // Numbers (hours, quantity, sqft)
                'boolean',      // Yes/No
                'text',         // Text input
                'selection',    // Dropdown/select
                'calculated'    // Auto-calculated from other params
            ])->default('numeric');
            $table->decimal('default_value', 10, 2)->nullable();
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->string('unit')->nullable(); // e.g., "hours", "sqft", "units", "%"
            $table->decimal('cost_per_unit', 10, 2)->default(0); // Cost per unit of this parameter
            $table->decimal('calculated_cost', 10, 2)->default(0); // Final calculated cost
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_user_editable')->default(true); // Can client/admin adjust this?
            $table->json('calculation_formula')->nullable(); // For complex calculations
            $table->json('validation_rules')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['component_id', 'sort_order']);
        });

        // 2. Create tier_recommendation_rules table (for the calculation engine)
        Schema::create('tier_recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->text('description')->nullable();
            $table->enum('input_category', [
                'issue_severity',           // Urgent, High, Medium, Low
                'property_use_lifestyle',   // Owner-occupied, Rental, High-traffic, etc.
                'property_type_complexity', // Single-family, Multi-unit, Luxury, etc.
                'structural_access',        // Easy, Moderate, Difficult, Complex
                'property_age',             // New, Moderate, Aging, Historic
                'system_complexity',        // Basic, Standard, Advanced, Premium
                'environmental_factors'     // Climate, Terrain, Exposure
            ]);
            $table->json('condition_criteria'); // The conditions that trigger this rule
            $table->integer('complexity_score')->default(0); // 1-100 weighted score
            $table->integer('priority_weight')->default(1); // How important is this factor?
            $table->json('recommended_adjustments'); // What to adjust in pricing/components
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('input_category');
            $table->index(['is_active', 'sort_order']);
        });

        // 3. Create property_complexity_scores table (calculated for each property)
        Schema::create('property_complexity_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('inspection_id')->nullable()->constrained()->onDelete('set null');
            
            // Individual factor scores (0-100 each)
            $table->integer('issue_severity_score')->default(0);
            $table->integer('lifestyle_score')->default(0);
            $table->integer('complexity_score')->default(0);
            $table->integer('access_difficulty_score')->default(0);
            $table->integer('age_score')->default(0);
            $table->integer('system_score')->default(0);
            $table->integer('environmental_score')->default(0);
            
            // Weighted total
            $table->integer('total_complexity_score')->default(0);
            
            // Calculated recommendations
            $table->string('recommended_tier')->nullable(); // Which tier/product to recommend
            $table->integer('recommended_visit_frequency')->nullable(); // Visits per year
            $table->enum('recommended_skill_level', ['basic', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->decimal('recommended_base_price', 10, 2)->nullable();
            $table->json('score_breakdown')->nullable(); // Detailed breakdown of how score was calculated
            $table->json('applied_rules')->nullable(); // Which rules were applied
            
            $table->timestamp('calculated_at');
            $table->foreignId('calculated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('property_id');
            $table->index('total_complexity_score');
        });

        // 4. Add complexity tracking to properties table
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('current_complexity_score')->default(0)->after('status');
            $table->string('recommended_tier')->nullable()->after('current_complexity_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['current_complexity_score', 'recommended_tier']);
        });

        Schema::dropIfExists('property_complexity_scores');
        Schema::dropIfExists('tier_recommendation_rules');
        Schema::dropIfExists('component_parameters');
    }
};
