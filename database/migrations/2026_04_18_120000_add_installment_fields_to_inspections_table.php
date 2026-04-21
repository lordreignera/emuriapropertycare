<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // Which payment plan the client chose when work started
            $table->enum('payment_plan', ['full', 'installment'])->default('full')->after('work_payment_cadence');
            // How many installments total (12 for monthly, 1 for full)
            $table->unsignedTinyInteger('installment_months')->default(12)->after('payment_plan');
            // How many installments have been paid so far
            $table->unsignedTinyInteger('installments_paid')->default(0)->after('installment_months');
            // Locked/frozen ARP total at the time plan was chosen
            $table->decimal('arp_total_locked', 10, 2)->nullable()->after('installments_paid');
            // Per-installment charge amount (arp_total_locked / installment_months)
            $table->decimal('installment_amount', 10, 2)->nullable()->after('arp_total_locked');
            // Set when all installments are paid (full plan: set immediately on first payment)
            $table->timestamp('arp_fully_paid_at')->nullable()->after('installment_amount');
            // Date the next installment is due (installment plan only)
            $table->date('next_installment_due_date')->nullable()->after('arp_fully_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'payment_plan',
                'installment_months',
                'installments_paid',
                'arp_total_locked',
                'installment_amount',
                'arp_fully_paid_at',
                'next_installment_due_date',
            ]);
        });
    }
};
