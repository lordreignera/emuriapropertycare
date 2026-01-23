<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactive_cost_assumptions', function (Blueprint $table) {
            $table->id();
            $table->string('severity_level', 20)->unique();
            $table->decimal('typical_cost', 10, 2);
            $table->decimal('annual_probability', 4, 2);
            $table->decimal('claimable_fraction', 4, 2);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactive_cost_assumptions');
    }
};
