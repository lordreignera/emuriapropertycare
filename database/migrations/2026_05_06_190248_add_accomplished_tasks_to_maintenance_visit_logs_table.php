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
        Schema::table('maintenance_visit_logs', function (Blueprint $table) {
            $table->json('accomplished_tasks')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_visit_logs', function (Blueprint $table) {
            $table->dropColumn('accomplished_tasks');
        });
    }
};
