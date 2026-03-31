<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->decimal('bdc_distance_km', 8, 2)->nullable()->after('bdc_visits_per_year');
            $table->decimal('bdc_time_minutes', 8, 2)->nullable()->after('bdc_distance_km');
            $table->decimal('bdc_rate_per_km', 8, 2)->nullable()->after('bdc_time_minutes');
            $table->decimal('bdc_rate_per_minute', 8, 2)->nullable()->after('bdc_rate_per_km');
            $table->decimal('bdc_per_visit', 10, 2)->nullable()->after('bdc_rate_per_minute');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'bdc_distance_km',
                'bdc_time_minutes',
                'bdc_rate_per_km',
                'bdc_rate_per_minute',
                'bdc_per_visit',
            ]);
        });
    }
};
