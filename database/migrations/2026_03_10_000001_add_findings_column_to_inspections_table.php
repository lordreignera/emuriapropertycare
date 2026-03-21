<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('inspections', 'findings')) {
                $table->json('findings')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('inspections', 'overall_condition')) {
                $table->string('overall_condition')->nullable()->after('findings');
            }
            if (!Schema::hasColumn('inspections', 'inspector_notes')) {
                $table->text('inspector_notes')->nullable()->after('overall_condition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumnIfExists('findings');
            $table->dropColumnIfExists('overall_condition');
            $table->dropColumnIfExists('inspector_notes');
        });
    }
};
