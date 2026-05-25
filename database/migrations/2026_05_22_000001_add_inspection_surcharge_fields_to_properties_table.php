<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('has_high_pitched_roof')->default(false)->after('residential_units')
                ->comment('Adds $75 surcharge to inspection fee');
            $table->boolean('has_crawl_space')->default(false)->after('has_high_pitched_roof')
                ->comment('Adds $50 surcharge to inspection fee');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['has_high_pitched_roof', 'has_crawl_space']);
        });
    }
};
