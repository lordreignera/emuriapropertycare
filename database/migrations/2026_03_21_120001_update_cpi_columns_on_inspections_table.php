<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // Change cpi_total_score from integer to decimal so it can hold 99.2 etc.
            $table->decimal('cpi_total_score', 5, 1)->nullable()->change();

            // Per-system CPI breakdown stored as JSON
            $table->json('system_scores')->nullable()->after('cpi_total_score');

            // Asset Stability Index (formula TBD — placeholder for now)
            $table->decimal('asi_score', 5, 1)->nullable()->after('system_scores');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->integer('cpi_total_score')->nullable()->change();
            $table->dropColumn(['system_scores', 'asi_score']);
        });
    }
};
