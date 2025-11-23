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
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->after('approved_by')->constrained('users')->onDelete('set null');
            $table->foreignId('inspector_id')->nullable()->after('project_manager_id')->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable()->after('inspector_id');
            $table->timestamp('inspection_scheduled_at')->nullable()->after('assigned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropForeign(['inspector_id']);
            $table->dropColumn(['project_manager_id', 'inspector_id', 'assigned_at', 'inspection_scheduled_at']);
        });
    }
};
