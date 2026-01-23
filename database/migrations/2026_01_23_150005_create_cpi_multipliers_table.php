<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpi_multipliers', function (Blueprint $table) {
            $table->id();
            $table->string('band_code', 10)->unique();
            $table->decimal('multiplier', 4, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('band_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpi_multipliers');
    }
};
