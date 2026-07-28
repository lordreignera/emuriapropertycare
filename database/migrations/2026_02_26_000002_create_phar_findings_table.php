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
            $table->unsignedBigInteger('system_id')->nullable();
            $table->unsignedBigInteger('subsystem_id')->nullable();
            $table->unsignedBigInteger('parent_finding_id')->nullable();
            
            // Finding Details
            $table->string('task_question')->nullable(); // From PHAR template
            $table->string('category')->nullable(); // From PHAR template (Bathroom, Plumbing, etc.)
            $table->enum('finding_type', ['stand_alone', 'cascading'])->default('stand_alone');
            $table->enum('severity', ['low', 'moderate', 'high', 'critical'])->default('moderate');
            $table->json('impact_categories')->nullable();
            $table->enum('priority', ['1', '2', '3'])->default('2'); // 1=High, 2=Medium, 3=Low
            $table->boolean('included_yn')->default(false); // Included in care package Y/N
            
            // Labour & Material Costs
            $table->decimal('labour_hours', 8, 2)->default(0); // Hours of labour required
            $table->decimal('material_cost', 10, 2)->default(0); // Material cost in dollars
            
            // Additional Info
            $table->text('notes')->nullable();
            $table->json('photo_ids')->nullable(); // Array of photo references
            $table->text('plain_language_definition')->nullable();
            $table->text('observed_condition')->nullable();
            $table->text('plain_language_meaning')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->text('consequence_if_ignored')->nullable();
            $table->text('remediation_strategy')->nullable();
            $table->text('stewardship_strategy')->nullable();
            $table->text('management_strategy')->nullable();
            $table->enum('workflow_status', [
                'observed',
                'decision_pending',
                'monitoring',
                'declined',
                'roadmapped',
                'quoted',
                'approved',
                'in_remediation',
                'remediated',
                'verification_pending',
                'verified',
            ])->default('observed');
            
            $table->timestamps();
            
            $table->index(['inspection_id', 'included_yn']);
            $table->index('property_id');
            $table->index('system_id');
            $table->index('subsystem_id');
            $table->index('parent_finding_id');
            $table->index(['severity', 'finding_type']);
        });
        
        // Note: PHAR calculation columns on the inspections table are defined
        // in the base create_inspections_table migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Inspection columns are part of the base table and dropped with it.
        Schema::dropIfExists('phar_findings');
    }
};
