<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('source', ['client_dashboard', 'project_page', 'property_page', 'admin_created'])
                ->default('client_dashboard');
            $table->enum('request_type', ['emergency', 'repair', 'change_request']);
            $table->enum('urgency', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->string('title', 180);
            $table->text('description');
            $table->string('requested_location', 180)->nullable();
            $table->json('items_reported')->nullable();
            $table->json('photos')->nullable();
            $table->json('floor_plan_pin')->nullable();
            $table->string('preferred_visit_window', 180)->nullable();

            $table->enum('status', [
                'submitted',
                'triaged',
                'awaiting_assessment',
                'assessed',
                'quotation_shared',
                'client_approved',
                'in_progress',
                'resolved',
                'cancelled',
            ])->default('submitted');

            $table->text('triage_notes')->nullable();
            $table->text('assessment_summary')->nullable();
            $table->foreignId('quotation_id')->nullable()->constrained('inspection_quotations')->nullOnDelete();
            $table->foreignId('approved_change_order_id')->nullable()->constrained('change_orders')->nullOnDelete();
            $table->foreignId('created_inspection_id')->nullable()->constrained('inspections')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('triaged_at')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'urgency']);
            $table->index(['property_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
