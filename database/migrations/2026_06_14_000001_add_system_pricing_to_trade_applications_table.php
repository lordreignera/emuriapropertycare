<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->json('system_pricing')->nullable()->after('subsystem_ids');
        });
    }

    public function down(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->dropColumn('system_pricing');
        });
    }
};
