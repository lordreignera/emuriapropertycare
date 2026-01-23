<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stewardship_loss_reductions', function (Blueprint $table) {
            $table->id();
            $table->string('cpi_band', 10)->unique();
            $table->decimal('loss_reduction', 4, 2);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('cpi_band');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stewardship_loss_reductions');
    }
};
