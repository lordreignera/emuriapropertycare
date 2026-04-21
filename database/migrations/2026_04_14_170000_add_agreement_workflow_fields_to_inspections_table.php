<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('inspections', 'etogo_signed_by')) {
                $table->foreignId('etogo_signed_by')->nullable()->after('client_acknowledgment')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('inspections', 'etogo_signed_at')) {
                $table->timestamp('etogo_signed_at')->nullable()->after('etogo_signed_by');
            }

            if (!Schema::hasColumn('inspections', 'planned_start_date')) {
                $table->date('planned_start_date')->nullable()->after('etogo_signed_at');
            }

            if (!Schema::hasColumn('inspections', 'estimated_duration_days')) {
                $table->unsignedInteger('estimated_duration_days')->nullable()->after('planned_start_date');
            }

            if (!Schema::hasColumn('inspections', 'target_completion_date')) {
                $table->date('target_completion_date')->nullable()->after('estimated_duration_days');
            }

            if (!Schema::hasColumn('inspections', 'schedule_blocked_reason')) {
                $table->string('schedule_blocked_reason', 1000)->nullable()->after('target_completion_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            if (Schema::hasColumn('inspections', 'etogo_signed_by')) {
                $table->dropConstrainedForeignId('etogo_signed_by');
            }

            if (Schema::hasColumn('inspections', 'etogo_signed_at')) {
                $table->dropColumn('etogo_signed_at');
            }

            if (Schema::hasColumn('inspections', 'planned_start_date')) {
                $table->dropColumn('planned_start_date');
            }

            if (Schema::hasColumn('inspections', 'estimated_duration_days')) {
                $table->dropColumn('estimated_duration_days');
            }

            if (Schema::hasColumn('inspections', 'target_completion_date')) {
                $table->dropColumn('target_completion_date');
            }

            if (Schema::hasColumn('inspections', 'schedule_blocked_reason')) {
                $table->dropColumn('schedule_blocked_reason');
            }
        });
    }
};
