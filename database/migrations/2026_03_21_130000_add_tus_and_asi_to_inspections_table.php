<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // Inspector-entered Tenant Underwriting Score (0-100)
            $table->decimal('tus_score', 5, 1)->nullable()->default(75)->after('asi_score');
            // Human-readable rating labels computed from CPI and ASI
            $table->string('cpi_rating', 60)->nullable()->after('tus_score');
            $table->string('asi_rating', 60)->nullable()->after('cpi_rating');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['tus_score', 'cpi_rating', 'asi_rating']);
        });
    }
};
