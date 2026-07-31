<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_subsystems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_system_id')
                ->constrained('building_systems')
                ->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['building_system_id', 'slug'],
                'building_subsystem_system_slug_unique'
            );

            $table->index(
                ['building_system_id', 'sort_order'],
                'building_subsystem_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_subsystems');
    }
};
