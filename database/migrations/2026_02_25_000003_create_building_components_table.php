<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_subsystem_id')
                ->constrained('building_subsystems')
                ->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 180);
            $table->string('slug', 190);
            $table->text('description')->nullable();
            $table->string('default_trade', 120)->nullable();
            $table->jsonb('aliases')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['building_subsystem_id', 'slug'],
                'building_component_subsystem_slug_unique'
            );

            $table->index(
                ['building_subsystem_id', 'sort_order'],
                'building_component_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_components');
    }
};
