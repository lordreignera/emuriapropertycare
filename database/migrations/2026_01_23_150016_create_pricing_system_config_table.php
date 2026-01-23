<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_system_config', function (Blueprint $table) {
            $table->id();
            $table->string('config_key', 100)->unique();
            $table->text('config_value');
            $table->string('data_type', 20)->default('text');
            $table->string('config_group', 50)->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('updated_at');
            
            $table->index(['config_group', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_system_config');
    }
};
