<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            $table->enum('category', ['labor', 'materials', 'equipment', 'permits', 'other'])->default('labor');
            $table->string('item_name');
            
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('covered_value', 10, 2)->default(0); // From tier
            $table->decimal('billable_value', 10, 2)->default(0); // Client pays
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->decimal('variance', 10, 2)->default(0); // actual - estimated
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
