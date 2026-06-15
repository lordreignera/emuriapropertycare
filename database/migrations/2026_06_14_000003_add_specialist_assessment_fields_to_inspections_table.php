<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->json('specialist_assessment_breakdown')->nullable()->after('inspection_fee_amount');
            $table->decimal('specialist_trade_cost', 10, 2)->default(0)->after('specialist_assessment_breakdown');
            $table->decimal('specialist_client_price', 10, 2)->default(0)->after('specialist_trade_cost');
            $table->decimal('specialist_margin_amount', 10, 2)->default(0)->after('specialist_client_price');
            $table->string('specialist_pricing_currency', 3)->nullable()->after('specialist_margin_amount');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'specialist_assessment_breakdown',
                'specialist_trade_cost',
                'specialist_client_price',
                'specialist_margin_amount',
                'specialist_pricing_currency',
            ]);
        });
    }
};
