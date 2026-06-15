<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->json('pricing_units')->nullable()->after('availability');
            $table->decimal('minimum_service_charge', 10, 2)->nullable()->after('pricing_units');
            $table->string('emergency_premium')->nullable()->after('minimum_service_charge');
            $table->string('travel_charge_policy')->nullable()->after('emergency_premium');
            $table->string('material_policy')->nullable()->after('travel_charge_policy');
            $table->string('equipment_policy')->nullable()->after('material_policy');
            $table->string('disposal_policy')->nullable()->after('equipment_policy');
            $table->string('standard_warranty')->nullable()->after('disposal_policy');
            $table->text('pricing_notes')->nullable()->after('standard_warranty');
            $table->json('sample_activity_prices')->nullable()->after('pricing_notes');
        });
    }

    public function down(): void
    {
        Schema::table('trade_applications', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_units',
                'minimum_service_charge',
                'emergency_premium',
                'travel_charge_policy',
                'material_policy',
                'equipment_policy',
                'disposal_policy',
                'standard_warranty',
                'pricing_notes',
                'sample_activity_prices',
            ]);
        });
    }
};
