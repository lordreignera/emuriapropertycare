<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('inspections', 'scientific_final_monthly')) {
                $table->decimal('scientific_final_monthly', 10, 2)->default(0)->after('base_package_price_snapshot');
            }
            if (!Schema::hasColumn('inspections', 'scientific_final_annual')) {
                $table->decimal('scientific_final_annual', 10, 2)->default(0)->after('scientific_final_monthly');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['scientific_final_monthly', 'scientific_final_annual']);
        });
    }
};
