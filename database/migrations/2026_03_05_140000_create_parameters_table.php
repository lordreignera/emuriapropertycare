<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_key')->unique();
            $table->decimal('parameter_value', 12, 6)->default(0);
            $table->string('group_name')->default('base_service_pricing');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
