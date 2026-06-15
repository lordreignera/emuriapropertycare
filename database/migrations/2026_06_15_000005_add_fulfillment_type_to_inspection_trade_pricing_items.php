<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->string('fulfillment_type', 30)->default('trade_partner')->after('trade_company_name');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->dropColumn('fulfillment_type');
        });
    }
};
