<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->decimal('trade_cost_annual', 10, 2)->default(0)->after('fmc_monthly');
            $table->decimal('trade_client_price_annual', 10, 2)->default(0)->after('trade_cost_annual');
            $table->decimal('trade_margin_annual', 10, 2)->default(0)->after('trade_client_price_annual');
        });

        Schema::table('inspection_quotations', function (Blueprint $table) {
            $table->decimal('approved_trade_cost', 10, 2)->default(0)->after('approved_material_cost');
            $table->decimal('approved_trade_client_price', 10, 2)->default(0)->after('approved_trade_cost');
            $table->decimal('approved_trade_margin', 10, 2)->default(0)->after('approved_trade_client_price');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'approved_trade_cost',
                'approved_trade_client_price',
                'approved_trade_margin',
            ]);
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'trade_cost_annual',
                'trade_client_price_annual',
                'trade_margin_annual',
            ]);
        });
    }
};
