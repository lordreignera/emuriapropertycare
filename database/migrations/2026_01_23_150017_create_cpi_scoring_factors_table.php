<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpi_scoring_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('cpi_domains')->onDelete('cascade');
            $table->string('factor_code', 50);
            $table->string('factor_label', 200);
            $table->string('field_type', 30); // yes_no, dropdown, numeric, calculated
            $table->string('lookup_table', 50)->nullable(); // supply_line_materials, age_brackets, etc.
            $table->integer('max_points');
            $table->text('calculation_rule')->nullable(); // JSON or formula
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('help_text')->nullable();
            $table->timestamps();
            
            $table->unique(['domain_id', 'factor_code']);
            $table->index(['domain_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpi_scoring_factors');
    }
};
