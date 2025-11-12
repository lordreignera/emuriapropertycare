<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope_of_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('inspection_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict'); // PM
            
            $table->text('description')->nullable();
            
            // Work Items organized by area sections (from inspection)
            // Each item: {area, issue, location, spot, est_cost, coverage_status, billable_amount, tier_level}
            $table->json('work_items'); // Array of work items by section
            
            // Totals
            $table->decimal('total_estimated_cost', 10, 2)->default(0);
            $table->decimal('total_covered_amount', 10, 2)->default(0);
            $table->decimal('total_billable_amount', 10, 2)->default(0);
            $table->decimal('deposit_due', 10, 2)->default(0); // 50% of billable
            
            // Coverage breakdown by tier
            $table->integer('items_fully_covered')->default(0);
            $table->integer('items_partially_covered')->default(0);
            $table->integer('items_not_covered')->default(0);
            
            // Upgrade options offered
            $table->boolean('upgrade_offered')->default(false);
            $table->json('upgrade_options')->nullable(); // Available upgrade tiers with pricing
            
            // PM Approval
            $table->boolean('approved_by_pm')->default(false);
            $table->foreignId('pm_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('pm_approved_at')->nullable();
            
            // Client Approval
            $table->boolean('approved_by_client')->default(false);
            $table->timestamp('client_approved_at')->nullable();
            $table->text('client_notes')->nullable();
            
            $table->timestamps();
            
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope_of_works');
    }
};
