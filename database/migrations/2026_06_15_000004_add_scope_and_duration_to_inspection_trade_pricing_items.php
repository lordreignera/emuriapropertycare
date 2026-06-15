<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->string('scope_area')->nullable()->after('activity');
            $table->decimal('estimated_duration_hours', 10, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_trade_pricing_items', function (Blueprint $table) {
            $table->dropColumn(['scope_area', 'estimated_duration_hours']);
        });
    }
};
