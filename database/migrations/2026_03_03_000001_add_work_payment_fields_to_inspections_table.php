<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->decimal('work_payment_amount', 10, 2)->nullable()->after('inspection_fee_paid_at');
            $table->enum('work_payment_status', ['pending', 'paid', 'failed'])->default('pending')->after('work_payment_amount');
            $table->timestamp('work_payment_paid_at')->nullable()->after('work_payment_status');
            $table->string('work_stripe_payment_intent_id')->nullable()->after('work_payment_paid_at');

            $table->index('work_payment_status');
            $table->index('work_stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropIndex(['work_payment_status']);
            $table->dropIndex(['work_stripe_payment_intent_id']);
            $table->dropColumn([
                'work_payment_amount',
                'work_payment_status',
                'work_payment_paid_at',
                'work_stripe_payment_intent_id',
            ]);
        });
    }
};
