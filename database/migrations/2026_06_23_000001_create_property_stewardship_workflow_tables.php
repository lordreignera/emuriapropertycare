<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phar_findings', function (Blueprint $table) {
            $table->foreign('parent_finding_id')->references('id')->on('phar_findings')->nullOnDelete();
        });

        Schema::create('finding_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phar_finding_id')->constrained('phar_findings')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('evidence_type', ['photo', 'video', 'moisture_reading', 'thermal_image', 'measurement', 'document', 'drawing', 'note']);
            $table->string('file_path')->nullable();
            $table->string('value')->nullable();
            $table->string('unit', 40)->nullable();
            $table->text('description')->nullable();
            $table->text('location_note')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['phar_finding_id', 'evidence_type']);
            $table->index(['property_id', 'captured_at']);
        });

        Schema::create('finding_client_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phar_finding_id')->constrained('phar_findings')->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_quotation_id')->nullable()->constrained('inspection_quotations')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('decision', [
                'immediate_remediation',
                'scheduled_remediation',
                'financing_review',
                'stewardship_monitoring',
                'declined',
                'request_revision',
            ]);
            $table->date('scheduled_for')->nullable();
            $table->text('client_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['phar_finding_id', 'decision']);
            $table->index(['inspection_id', 'decision']);
        });

        Schema::create('remediation_roadmaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'superseded', 'completed'])->default('draft');
            $table->string('title')->default('Remediation Roadmap');
            $table->text('summary')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });

        Schema::create('remediation_roadmap_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remediation_roadmap_id')->constrained('remediation_roadmaps')->cascadeOnDelete();
            $table->foreignId('phar_finding_id')->constrained('phar_findings')->cascadeOnDelete();
            $table->foreignId('finding_client_decision_id')->nullable()->constrained('finding_client_decisions')->nullOnDelete();
            $table->enum('plan_type', ['immediate', 'twelve_month', 'twenty_four_month', 'financing', 'stewardship_monitoring', 'declined']);
            $table->date('target_date')->nullable();
            $table->unsignedInteger('priority_order')->default(0);
            $table->enum('status', ['planned', 'quoted', 'approved', 'in_progress', 'completed', 'deferred', 'cancelled'])->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['remediation_roadmap_id', 'plan_type', 'status'], 'roadmap_items_roadmap_plan_status_idx');
            $table->unique(['remediation_roadmap_id', 'phar_finding_id'], 'roadmap_finding_unique');
        });

        Schema::table('inspection_quotations', function (Blueprint $table) {
            $table->foreign('remediation_roadmap_id')
                ->references('id')
                ->on('remediation_roadmaps')
                ->nullOnDelete();
        });

        Schema::create('remediation_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_number', 60)->unique();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_quotation_id')->nullable()->constrained('inspection_quotations')->nullOnDelete();
            $table->foreignId('remediation_roadmap_id')->nullable()->constrained('remediation_roadmaps')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_trade_partner_id')->nullable()->constrained('trade_partners')->nullOnDelete();
            $table->string('title');
            $table->longText('scope_of_work')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed', 'verification_pending', 'verified', 'cancelled'])->default('draft');
            $table->date('scheduled_start_date')->nullable();
            $table->date('scheduled_end_date')->nullable();
            $table->decimal('budget_amount', 10, 2)->default(0);
            $table->json('materials')->nullable();
            $table->json('evidence_requirements')->nullable();
            $table->json('completion_requirements')->nullable();
            $table->json('verification_requirements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['inspection_quotation_id', 'status']);
        });

        Schema::create('remediation_work_order_findings', function (Blueprint $table) {
            $table->id();
            // Custom short FK/index names to stay under MySQL's 64-char identifier limit
            $table->unsignedBigInteger('remediation_work_order_id');
            $table->foreign('remediation_work_order_id', 'wof_work_order_fk')
                ->references('id')->on('remediation_work_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('phar_finding_id');
            $table->foreign('phar_finding_id', 'wof_phar_finding_fk')
                ->references('id')->on('phar_findings')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['remediation_work_order_id', 'phar_finding_id'], 'work_order_finding_unique');
        });

        Schema::create('work_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remediation_work_order_id')->constrained('remediation_work_orders')->cascadeOnDelete();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('event_type', [
                'created',
                'planned',
                'started',
                'progress',
                'completed',
                'verification_passed',
                'verification_failed',
                'change_order',
                'cancelled',
            ]);
            $table->timestamp('occurred_at')->nullable();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['remediation_work_order_id', 'event_type']);
            $table->index('occurred_at');
        });

        Schema::create('verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remediation_work_order_id')->nullable()->constrained('remediation_work_orders')->nullOnDelete();
            $table->foreignId('phar_finding_id')->nullable()->constrained('phar_findings')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'passed', 'failed', 'conditional'])->default('pending');
            $table->json('before_review')->nullable();
            $table->json('after_review')->nullable();
            $table->text('quality_notes')->nullable();
            $table->json('tests_performed')->nullable();
            $table->text('remaining_concerns')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['remediation_work_order_id', 'status']);
            $table->index(['phar_finding_id', 'status']);
        });

        Schema::create('verified_property_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phar_finding_id')->nullable()->constrained('phar_findings')->nullOnDelete();
            $table->foreignId('remediation_work_order_id')->nullable()->constrained('remediation_work_orders')->nullOnDelete();
            $table->foreignId('verification_record_id')->nullable()->constrained('verification_records')->nullOnDelete();
            $table->enum('fact_type', ['condition', 'repair', 'replacement', 'maintenance', 'warranty', 'owner_update'])->default('condition');
            $table->string('title');
            $table->longText('fact_summary')->nullable();
            $table->enum('source_type', ['etogo', 'approved_trade', 'owner_verified'])->default('etogo');
            $table->enum('reliability_level', ['verified_property_fact'])->default('verified_property_fact');
            $table->date('effective_date')->nullable();
            $table->json('materials')->nullable();
            $table->json('warranty')->nullable();
            $table->json('monitoring_requirements')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'fact_type']);
        });

        Schema::create('stewardship_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'superseded', 'completed'])->default('draft');
            $table->string('title')->default('Stewardship Plan');
            $table->string('inspection_frequency', 80)->nullable();
            $table->json('strategy')->nullable();
            $table->date('next_review_date')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });

        Schema::create('stewardship_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stewardship_plan_id')->constrained('stewardship_plans')->cascadeOnDelete();
            $table->foreignId('verified_property_fact_id')->nullable()->constrained('verified_property_facts')->nullOnDelete();
            $table->foreignId('phar_finding_id')->nullable()->constrained('phar_findings')->nullOnDelete();
            $table->string('activity_type', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('frequency', 80)->nullable();
            $table->date('next_due_date')->nullable();
            $table->enum('status', ['planned', 'due', 'completed', 'skipped', 'cancelled'])->default('planned');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['stewardship_plan_id', 'status']);
            $table->index('next_due_date');
        });

        Schema::create('performance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_property_fact_id')->nullable()->constrained('verified_property_facts')->nullOnDelete();
            $table->foreignId('phar_finding_id')->nullable()->constrained('phar_findings')->nullOnDelete();
            $table->foreignId('remediation_work_order_id')->nullable()->constrained('remediation_work_orders')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('recurrence_status', ['none', 'recurring', 'unknown'])->default('unknown');
            $table->boolean('system_improved')->nullable();
            $table->boolean('risk_reduced')->nullable();
            $table->unsignedTinyInteger('trade_score')->nullable();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'recorded_at']);
            $table->index(['verified_property_fact_id', 'recurrence_status'], 'performance_fact_recurrence_idx');
        });

        Schema::create('owner_submitted_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('linked_property_fact_id')->nullable()->constrained('verified_property_facts')->nullOnDelete();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->json('evidence')->nullable();
            $table->enum('status', ['recorded', 'pending_verification', 'accepted_verified', 'rejected', 'unverified_claim'])->default('recorded');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_submitted_updates');
        Schema::dropIfExists('performance_records');
        Schema::dropIfExists('stewardship_plan_items');
        Schema::dropIfExists('stewardship_plans');
        Schema::dropIfExists('verified_property_facts');
        Schema::dropIfExists('verification_records');
        Schema::dropIfExists('work_order_events');
        Schema::dropIfExists('remediation_work_order_findings');
        Schema::dropIfExists('remediation_work_orders');

        Schema::table('inspection_quotations', function (Blueprint $table) {
            $table->dropForeign(['remediation_roadmap_id']);
        });

        Schema::dropIfExists('remediation_roadmap_items');
        Schema::dropIfExists('remediation_roadmaps');
        Schema::dropIfExists('finding_client_decisions');
        Schema::dropIfExists('finding_evidence');

        Schema::table('phar_findings', function (Blueprint $table) {
            $table->dropForeign(['parent_finding_id']);
        });
    }
};
