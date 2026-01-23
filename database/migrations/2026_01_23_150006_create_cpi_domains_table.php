<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpi_domains', function (Blueprint $table) {
            $table->id();
            $table->integer('domain_number')->unique();
            $table->string('domain_name', 100);
            $table->string('domain_code', 50)->unique();
            $table->integer('max_possible_points');
            $table->text('description')->nullable();
            $table->string('calculation_method', 50)->default('sum'); // sum, max, formula
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
            $table->index('domain_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpi_domains');
    }
};
