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
            // JSON array of tool assignment IDs used during this visit
            $table->json('tools_used')->nullable()->after('after_photos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_visit_logs', function (Blueprint $table) {
            $table->dropColumn('tools_used');
        });
    }
};
