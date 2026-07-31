<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_template_settings', function (Blueprint $table) {
            $table->id();
            $table->string('task_question', 255);
            $table->foreignId('building_system_id')->nullable()->constrained('building_systems')->nullOnDelete();
            $table->foreignId('building_subsystem_id')->nullable()->constrained('building_subsystems')->nullOnDelete();
            $table->foreignId('building_component_id')->nullable()->constrained('building_components')->nullOnDelete();
            $table->string('category', 120)->nullable();
            $table->boolean('default_included')->default(true);
            $table->text('default_notes')->nullable();
            $table->json('default_recommendations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['building_system_id', 'building_subsystem_id'], 'fts_building_system_subsystem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_template_settings');
    }
};
