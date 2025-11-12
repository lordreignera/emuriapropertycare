<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('scope_of_work_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            
            // Financial Summary
            $table->decimal('total_project_value', 10, 2)->default(0); // Covered + Billable
            $table->decimal('covered_by_subscription', 10, 2)->default(0); // Tier covered amount
            $table->decimal('client_billable_amount', 10, 2)->default(0); // What client pays
            $table->decimal('deposit_required', 10, 2)->default(0); // Usually 50% of billable
            $table->decimal('balance_due', 10, 2)->default(0); // Remaining after deposit
            
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            
            // Scheduled Time
            $table->json('scheduled_days')->nullable(); // Array of {date, start_time, end_time, hours}
            $table->decimal('total_scheduled_hours', 5, 2)->default(0);
            
            // Line Items (from scope of work by category)
            $table->json('line_items')->nullable(); // Detailed breakdown by system/area
            
            // Scope Summary
            $table->json('scope_summary')->nullable(); // Condensed list of work categories
            
            // Subscription Value Recognition
            $table->decimal('subscription_savings', 10, 2)->default(0); // Same as covered_by_subscription
            $table->text('savings_message')->nullable();
            
            // Upgrade Options
            $table->boolean('upgrade_offered')->default(false);
            $table->string('upgrade_selected')->nullable(); // enhanced, enterprise
            $table->boolean('immediate_activation')->default(false);
            $table->decimal('upgrade_fee', 10, 2)->nullable();
            $table->decimal('prorated_upgrade_fee', 10, 2)->nullable();
            
            // Approval
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'revision_requested'])->default('pending');
            $table->timestamp('client_approved_at')->nullable();
            $table->string('client_signature')->nullable(); // Digital signature or file path
            $table->text('client_notes')->nullable();
            
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
