<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_size_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_name', 100)->unique();
            $table->decimal('setting_value', 10, 2)->nullable();
            $table->string('data_type', 20)->default('decimal');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('setting_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_size_settings');
    }
};
