<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->json('agreed_subsystem_pricing')->nullable()->after('subsystem_pricing');
            $table->json('agreed_custom_coverage')->nullable()->after('custom_coverage');
            $table->timestamp('pricing_agreed_at')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->dropColumn([
                'agreed_subsystem_pricing',
                'agreed_custom_coverage',
                'pricing_agreed_at',
            ]);
        });
    }
};
