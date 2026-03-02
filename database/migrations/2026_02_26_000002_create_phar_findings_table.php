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
        Schema::create('phar_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            
            // Finding Details
            $table->string('task_question')->nullable(); // From PHAR template
            $table->string('category')->nullable(); // From PHAR template (Bathroom, Plumbing, etc.)
            $table->enum('priority', ['1', '2', '3'])->default('2'); // 1=High, 2=Medium, 3=Low
            $table->boolean('included_yn')->default(false); // Included in care package Y/N
            
            // Labour & Material Costs
            $table->decimal('labour_hours', 8, 2)->default(0); // Hours of labour required
            $table->decimal('material_cost', 10, 2)->default(0); // Material cost in dollars
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('photo_ids')->nullable(); // Array of photo references
            
            $table->timestamps();
            
            $table->index(['inspection_id', 'included_yn']);
            $table->index('property_id');
        });
        
        // Add PHAR calculation fields to inspections table
        Schema::table('inspections', function (Blueprint $table) {
            // BDC Snapshot (at inspection time)
            $table->decimal('bdc_annual', 10, 2)->default(0);
            $table->decimal('bdc_monthly', 10, 2)->default(0);
            
            // FRLC (Findings Remediation Labour Cost)
            $table->decimal('frlc_annual', 10, 2)->default(0);
            $table->decimal('frlc_monthly', 10, 2)->default(0);
            $table->decimal('labour_hourly_rate', 10, 2)->default(165); // Snapshot of rate
            
            // FMC (Findings Material Cost)
            $table->decimal('fmc_annual', 10, 2)->default(0);
            $table->decimal('fmc_monthly', 10, 2)->default(0);
            
            // TRC (Total Remediation Cost)
            $table->decimal('trc_annual', 10, 2)->default(0);
            $table->decimal('trc_monthly', 10, 2)->default(0);
            
            // ARP (Annual Recurring Price - monthly)
            $table->decimal('arp_monthly', 10, 2)->default(0);
            
            // Condition Score (0-100 mapped from CPI score)
            $table->integer('condition_score')->default(0);
            
            // Tier Assignment (Dual-Gate System)
            $table->string('tier_score')->nullable(); // From condition score
            $table->string('tier_arp')->nullable(); // From ARP pressure
            $table->string('tier_final')->nullable(); // max(tier_score, tier_arp)
            
            // Multiplier & Final Pricing
            $table->decimal('multiplier_final', 4, 2)->default(1.00);
            $table->decimal('arp_equivalent_final', 10, 2)->default(0);
            
            // Base Package Price (floor)
            $table->decimal('base_package_price_snapshot', 10, 2)->default(0);
            
            // Per-Unit Breakdown
            $table->integer('units_for_calculation')->default(1);
            $table->decimal('bdc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('frlc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('fmc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('trc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('final_monthly_per_unit', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'bdc_annual',
                'bdc_monthly',
                'frlc_annual',
                'frlc_monthly',
                'labour_hourly_rate',
                'fmc_annual',
                'fmc_monthly',
                'trc_annual',
                'trc_monthly',
                'arp_monthly',
                'condition_score',
                'tier_score',
                'tier_arp',
                'tier_final',
                'multiplier_final',
                'arp_equivalent_final',
                'base_package_price_snapshot',
                'units_for_calculation',
                'bdc_per_unit_annual',
                'frlc_per_unit_annual',
                'fmc_per_unit_annual',
                'trc_per_unit_annual',
                'final_monthly_per_unit',
            ]);
        });
        
        Schema::dropIfExists('phar_findings');
    }
};
