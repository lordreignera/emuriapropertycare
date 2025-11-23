<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tiers', function (Blueprint $table) {
            $table->string('stripe_price_id_monthly')->nullable()->after('annual_price');
            $table->string('stripe_price_id_annual')->nullable()->after('stripe_price_id_monthly');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiers', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id_monthly', 'stripe_price_id_annual']);
        });
    }
};
