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
        Schema::create('bdc_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_label');
            $table->string('setting_description')->nullable();
            $table->decimal('setting_value', 10, 2);
            $table->string('unit')->nullable(); // e.g., '$/hr', '%', 'hours', 'visits'
            $table->enum('setting_type', ['rate', 'percentage', 'count', 'hours'])->default('rate');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('setting_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bdc_settings');
    }
};
