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
        // work_payment_cadence was enum('monthly','annual') — add 'full' and 'per_visit'
        DB::statement("ALTER TABLE inspections MODIFY work_payment_cadence ENUM('full','per_visit','monthly','annual') NULL");

        // payment_plan was enum('full','installment') — add 'per_visit'
        DB::statement("ALTER TABLE inspections MODIFY payment_plan ENUM('full','per_visit','installment') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE inspections MODIFY work_payment_cadence ENUM('monthly','annual') NULL");
        DB::statement("ALTER TABLE inspections MODIFY payment_plan ENUM('full','installment') NULL");
    }
};
