<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpi_band_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('band_code', 10)->unique();
            $table->string('band_name', 50);
            $table->integer('min_score');
            $table->integer('max_score')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpi_band_ranges');
    }
};
