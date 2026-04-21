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
        Schema::table('inspection_tool_assignments', function (Blueprint $table) {
            // Notes recorded when a quantity is assigned out
            $table->text('assign_notes')->nullable()->after('finding_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspection_tool_assignments', function (Blueprint $table) {
            $table->dropColumn('assign_notes');
        });
    }
};
