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
        Schema::create('inspection_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            
            // Material details
            $table->string('material_name')->nullable(); // Material/Part description
            $table->text('description')->nullable(); // Question/details
            $table->decimal('quantity', 10, 2)->default(0); // Quantity
            $table->string('unit', 20)->nullable(); // tube, ea, gal, lot, etc.
            $table->decimal('unit_cost', 10, 2)->default(0); // Unit cost in dollars
            $table->decimal('line_total', 10, 2)->default(0); // Calculated: qty × unit_cost
            $table->text('notes')->nullable();
            $table->string('category')->nullable(); // Plumbing, Electrical, Minor Rep, etc.
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('inspection_id');
            $table->index('property_id');
        });
        
        // Note: property_size_psf, estimated_task_hours, minimum_required_hours
        // are defined in the base create_inspections_table migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Inspection columns are part of the base table and dropped with it.
        Schema::dropIfExists('inspection_materials');
    }
};
