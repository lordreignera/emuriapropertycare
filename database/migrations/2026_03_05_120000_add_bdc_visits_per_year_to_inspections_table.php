<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->decimal('bdc_visits_per_year', 10, 2)
                ->nullable()
                ->after('minimum_required_hours');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn('bdc_visits_per_year');
        });
    }
};
