<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixed_use_calculation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_name', 100)->unique();
            $table->decimal('setting_value', 5, 2);
            $table->text('description')->nullable();
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixed_use_calculation_settings');
    }
};
