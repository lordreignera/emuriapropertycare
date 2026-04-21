<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fmc_material_settings', function (Blueprint $table) {
            $table->id();
            $table->string('material_name', 150);
            $table->string('default_unit', 30)->default('ea');
            $table->decimal('default_unit_cost', 12, 2)->default(0);
            $table->decimal('hst_rate', 5, 2)->default(5.00)->comment('HST % applied to unit cost (e.g. 5 for 5%)');
            $table->decimal('pst_rate', 5, 2)->default(7.00)->comment('PST % applied to unit cost (e.g. 7 for 7%)');
            $table->text('description')->nullable();
            $table->foreignId('system_id')->nullable()->constrained('systems')->nullOnDelete();
            $table->foreignId('subsystem_id')->nullable()->constrained('subsystems')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['system_id', 'subsystem_id'], 'fms_system_subsystem_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fmc_material_settings');
    }
};
