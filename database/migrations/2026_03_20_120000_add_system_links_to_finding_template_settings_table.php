<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finding_template_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('finding_template_settings', 'system_id')) {
                $table->foreignId('system_id')->nullable()->after('task_question')->constrained('systems')->nullOnDelete();
            }

            if (!Schema::hasColumn('finding_template_settings', 'subsystem_id')) {
                $table->foreignId('subsystem_id')->nullable()->after('system_id')->constrained('subsystems')->nullOnDelete();
            }

            $table->index(['system_id', 'subsystem_id'], 'fts_system_subsystem_idx');
        });
    }

    public function down(): void
    {
        Schema::table('finding_template_settings', function (Blueprint $table) {
            if (Schema::hasColumn('finding_template_settings', 'subsystem_id')) {
                $table->dropConstrainedForeignId('subsystem_id');
            }

            if (Schema::hasColumn('finding_template_settings', 'system_id')) {
                $table->dropConstrainedForeignId('system_id');
            }

            $table->dropIndex('fts_system_subsystem_idx');
        });
    }
};
