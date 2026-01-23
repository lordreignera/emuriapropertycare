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
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade'); // Property for which inspection is scheduled
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade'); // Nullable since inspection can be scheduled before project creation
            $table->foreignId('inspector_id')->nullable()->constrained('users')->onDelete('restrict'); // Nullable since client schedules before inspector assigned
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null'); // PM
            
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            
            $table->text('summary')->nullable();
            
            // Comprehensive Inspection Categories (JSON arrays of items)
            // Each item: {issue, location, spot, notes, recommendations: [option1, option2, option3]}
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
            $table->json('improvement_projects')->nullable(); // Future/upsell opportunities
            
            $table->string('report_file')->nullable(); // PDF report path
            $table->json('photos')->nullable(); // Array of photo paths
            
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'approved', 'revision_needed'])->default('scheduled');
            
            // Inspection Fee Payment
            $table->enum('inspection_fee_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('inspection_fee_paid_at')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->decimal('inspection_fee_amount', 10, 2)->nullable();
            
            // Client Approval
            $table->boolean('approved_by_client')->default(false);
            $table->timestamp('client_approved_at')->nullable();
            $table->string('client_signature')->nullable(); // Signature image path
            $table->string('client_full_name')->nullable();
            $table->text('client_acknowledgment')->nullable();
            
            $table->timestamps();
            
            $table->index(['property_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index('inspector_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
