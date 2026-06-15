<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->string('travel_policy_document')->nullable()->after('travel_charge_policy');
            $table->string('material_policy_document')->nullable()->after('material_policy');
            $table->string('equipment_policy_document')->nullable()->after('equipment_policy');
            $table->string('disposal_policy_document')->nullable()->after('disposal_policy');
            $table->string('warranty_document')->nullable()->after('standard_warranty');
            $table->string('pricing_policy_document')->nullable()->after('pricing_notes');
        });
    }

    public function down(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->dropColumn([
                'travel_policy_document',
                'material_policy_document',
                'equipment_policy_document',
                'disposal_policy_document',
                'warranty_document',
                'pricing_policy_document',
            ]);
        });
    }
};
