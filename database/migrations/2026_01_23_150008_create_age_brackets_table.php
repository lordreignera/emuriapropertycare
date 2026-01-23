<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('age_brackets', function (Blueprint $table) {
            $table->id();
            $table->string('bracket_name', 50);
            $table->integer('min_age');
            $table->integer('max_age')->nullable();
            $table->integer('score_points');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_brackets');
    }
};
