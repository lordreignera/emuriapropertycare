<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->json('subsystem_pricing')->nullable()->after('system_pricing');
        });
    }

    public function down(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->dropColumn('subsystem_pricing');
        });
    }
};
