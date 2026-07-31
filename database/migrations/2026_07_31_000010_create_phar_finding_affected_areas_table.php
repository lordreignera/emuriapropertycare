<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phar_finding_affected_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phar_finding_id')->constrained('phar_findings')->cascadeOnDelete();
            $table->foreignId('building_system_id')->nullable()->constrained('building_systems')->nullOnDelete();
            $table->foreignId('building_subsystem_id')->nullable()->constrained('building_subsystems')->nullOnDelete();
            $table->foreignId('building_component_id')->nullable()->constrained('building_components')->nullOnDelete();
            $table->string('location', 255)->nullable();
            $table->text('impact_description')->nullable();
            $table->enum('severity', ['low', 'moderate', 'high', 'critical'])->default('moderate');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['phar_finding_id', 'sort_order'], 'phar_affected_area_order_idx');
            $table->index(['building_system_id', 'building_subsystem_id'], 'phar_affected_area_system_subsystem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phar_finding_affected_areas');
    }
};
