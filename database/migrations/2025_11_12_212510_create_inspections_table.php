<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();

            // ── Relationships ──────────────────────────────────────────────
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->unsignedBigInteger('technician_id')->nullable();
            $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');

            // ── Property & Owner Snapshot ──────────────────────────────────
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('property_code')->nullable();
            $table->string('property_name')->nullable();
            $table->text('property_address_snapshot')->nullable();
            $table->enum('property_type_snapshot', ['residential', 'commercial', 'mixed_use'])->nullable();
            $table->integer('property_year_built')->nullable();
            $table->integer('residential_units_snapshot')->nullable();
            $table->decimal('commercial_sqft_snapshot', 10, 2)->nullable();
            $table->decimal('mixed_use_weight_snapshot', 5, 2)->nullable();

            // ── Scheduling ─────────────────────────────────────────────────
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->string('weather_conditions')->nullable();

            // ── Content ────────────────────────────────────────────────────
            $table->text('summary')->nullable();
            $table->json('findings')->nullable();

            // ── Legacy Inspection Category JSON ────────────────────────────
            $table->json('interior_walls_trim_paint')->nullable();
            $table->json('windows_trim')->nullable();
            $table->json('doors_hardware')->nullable();
            $table->json('floors')->nullable();
            $table->json('bathrooms')->nullable();
            $table->json('kitchen')->nullable();
            $table->json('baseboards_trim')->nullable();
            $table->json('caulking_water_control')->nullable();
            $table->json('crown_moulding')->nullable();
            $table->json('electrical')->nullable();
            $table->json('plumbing')->nullable();
            $table->json('ventilation')->nullable();
            $table->json('exterior')->nullable();
            $table->json('roof_drainage')->nullable();
            $table->json('deck_stairs')->nullable();
            $table->json('landscaping_pruning')->nullable();
            $table->json('accessibility')->nullable();
            $table->json('garage')->nullable();
            $table->json('foundation_sump')->nullable();
            $table->json('improvement_projects')->nullable();

            $table->string('report_file')->nullable();
            $table->json('photos')->nullable();

            // ── Status & Assessment ────────────────────────────────────────
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'approved', 'revision_needed'])->default('scheduled');
            $table->enum('overall_condition', ['excellent', 'good', 'fair', 'poor', 'critical'])->nullable();
            $table->text('inspector_notes')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('risk_summary')->nullable();
            $table->text('photo_notes')->nullable();

            // ── CPI Scoring ────────────────────────────────────────────────
            $table->decimal('cpi_total_score', 5, 1)->nullable()->default(0);
            $table->json('system_scores')->nullable();
            $table->decimal('asi_score', 5, 1)->nullable();
            $table->decimal('tus_score', 5, 1)->nullable()->default(75);
            $table->string('cpi_rating', 60)->nullable();
            $table->string('asi_rating', 60)->nullable();

            // ── Size Factors ───────────────────────────────────────────────
            $table->decimal('residential_size_factor', 4, 2)->nullable();
            $table->decimal('commercial_size_factor', 4, 2)->nullable();
            $table->decimal('harmonised_size_factor', 4, 2)->nullable();

            // ── Property Measurements ──────────────────────────────────────
            $table->decimal('property_size_psf', 10, 2)->nullable();
            $table->decimal('estimated_task_hours', 10, 2)->nullable();
            $table->decimal('minimum_required_hours', 10, 2)->default(3);

            // ── BDC (Base Delivery Cost) ───────────────────────────────────
            $table->decimal('bdc_visits_per_year', 10, 2)->nullable();
            $table->decimal('bdc_distance_km', 8, 2)->nullable();
            $table->decimal('bdc_time_minutes', 8, 2)->nullable();
            $table->decimal('bdc_rate_per_km', 8, 2)->nullable();
            $table->decimal('bdc_rate_per_minute', 8, 2)->nullable();
            $table->decimal('bdc_per_visit', 10, 2)->nullable();
            $table->decimal('bdc_annual', 10, 2)->default(0);
            $table->decimal('bdc_monthly', 10, 2)->default(0);

            // ── FRLC (Findings Remediation Labour Cost) ───────────────────
            $table->decimal('frlc_annual', 10, 2)->default(0);
            $table->decimal('frlc_monthly', 10, 2)->default(0);
            $table->decimal('labour_hourly_rate', 10, 2)->default(165);

            // ── FMC (Findings Material Cost) ───────────────────────────────
            $table->decimal('fmc_annual', 10, 2)->default(0);
            $table->decimal('fmc_monthly', 10, 2)->default(0);

            // ── TRC (Total Remediation Cost) ───────────────────────────────
            $table->decimal('trc_annual', 10, 2)->default(0);
            $table->decimal('trc_monthly', 10, 2)->default(0);
            $table->decimal('trc_per_visit', 10, 2)->default(0); // trc_annual / bdc_visits_per_year
            // ── ARP (Annual Recurring Price) ───────────────────────────────
            $table->decimal('arp_monthly', 10, 2)->default(0);

            // ── Tier & Multiplier ──────────────────────────────────────────
            $table->integer('condition_score')->default(0);
            $table->string('tier_score')->nullable();
            $table->string('tier_arp')->nullable();
            $table->string('tier_final')->nullable();
            $table->decimal('multiplier_final', 4, 2)->default(1.00);
            $table->decimal('arp_equivalent_final', 10, 2)->default(0);

            // ── Final Pricing ──────────────────────────────────────────────
            $table->decimal('base_package_price_snapshot', 10, 2)->default(0);
            $table->decimal('scientific_final_monthly', 10, 2)->default(0);
            $table->decimal('scientific_final_annual', 10, 2)->default(0);
            $table->decimal('final_monthly_cost', 10, 2)->nullable();
            $table->decimal('final_annual_cost', 10, 2)->nullable();

            // ── Per-Unit Breakdown ─────────────────────────────────────────
            $table->integer('units_for_calculation')->default(1);
            $table->decimal('bdc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('frlc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('fmc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('trc_per_unit_annual', 10, 2)->default(0);
            $table->decimal('final_monthly_per_unit', 10, 2)->default(0);

            // ── Inspection Fee Payment ─────────────────────────────────────
            $table->enum('inspection_fee_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('inspection_fee_paid_at')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->decimal('inspection_fee_amount', 10, 2)->nullable();

            // ── Work Payment ───────────────────────────────────────────────
            $table->decimal('work_payment_amount', 10, 2)->nullable();
            $table->enum('work_payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('work_payment_paid_at')->nullable();
            $table->string('work_stripe_payment_intent_id')->nullable();
            $table->enum('work_payment_cadence', ['full', 'per_visit', 'monthly', 'annual'])->nullable();
            $table->enum('payment_plan', ['full', 'per_visit', 'installment'])->nullable();

            // ── Installment Plan ───────────────────────────────────────────
            $table->unsignedTinyInteger('installment_months')->default(12);
            $table->unsignedTinyInteger('installments_paid')->default(0);
            $table->decimal('arp_total_locked', 10, 2)->nullable();
            $table->decimal('installment_amount', 10, 2)->nullable();
            $table->timestamp('arp_fully_paid_at')->nullable();
            $table->date('next_installment_due_date')->nullable();

            // ── Agreement Workflow ─────────────────────────────────────────
            $table->foreignId('etogo_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('etogo_signed_at')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->unsignedInteger('estimated_duration_days')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->string('schedule_blocked_reason', 1000)->nullable();
            $table->json('work_schedule')->nullable();

            // ── Client Approval ────────────────────────────────────────────
            $table->boolean('approved_by_client')->default(false);
            $table->timestamp('client_approved_at')->nullable();
            $table->string('client_signature')->nullable();
            $table->string('client_full_name')->nullable();
            $table->text('client_acknowledgment')->nullable();

            $table->timestamps();

            // ── Indexes ────────────────────────────────────────────────────
            $table->index(['property_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index('inspector_id');
            $table->index('work_payment_status');
            $table->index('work_stripe_payment_intent_id');
            $table->index('cpi_total_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
