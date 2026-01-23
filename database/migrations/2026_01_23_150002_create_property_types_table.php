<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_code', 20)->unique();
            $table->string('type_name', 50);
            $table->boolean('uses_unit_count')->default(false);
            $table->boolean('uses_square_footage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('type_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_types');
    }
};
