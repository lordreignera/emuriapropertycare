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
