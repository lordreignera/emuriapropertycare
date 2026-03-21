<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fmc_material_settings', function (Blueprint $table) {
            $table->foreignId('system_id')
                  ->nullable()
                  ->after('description')
                  ->constrained('systems')
                  ->nullOnDelete();

            $table->foreignId('subsystem_id')
                  ->nullable()
                  ->after('system_id')
                  ->constrained('subsystems')
                  ->nullOnDelete();

            $table->index(['system_id', 'subsystem_id'], 'fms_system_subsystem_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fmc_material_settings', function (Blueprint $table) {
            $table->dropIndex('fms_system_subsystem_idx');
            $table->dropConstrainedForeignId('subsystem_id');
            $table->dropConstrainedForeignId('system_id');
        });
    }
};
