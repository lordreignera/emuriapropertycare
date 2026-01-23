<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residential_size_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('tier_name', 50);
            $table->integer('min_units');
            $table->integer('max_units')->nullable();
            $table->decimal('size_factor', 4, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residential_size_tiers');
    }
};
