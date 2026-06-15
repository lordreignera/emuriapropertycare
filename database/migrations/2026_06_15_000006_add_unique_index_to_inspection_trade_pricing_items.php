<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->unique(['inspection_id', 'finding_index'], 'inspection_trade_pricing_unique_finding');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->dropUnique('inspection_trade_pricing_unique_finding');
        });
    }
};
