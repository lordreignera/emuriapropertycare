<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fmc_material_settings', function (Blueprint $table) {
            $table->decimal('hst_rate', 5, 2)->default(5.00)->after('default_unit_cost')
                  ->comment('HST % applied to unit cost (e.g. 5 for 5%)');
            $table->decimal('pst_rate', 5, 2)->default(7.00)->after('hst_rate')
                  ->comment('PST % applied to unit cost (e.g. 7 for 7%)');
        });
    }

    public function down(): void
    {
        Schema::table('fmc_material_settings', function (Blueprint $table) {
            $table->dropColumn(['hst_rate', 'pst_rate']);
        });
    }
};
