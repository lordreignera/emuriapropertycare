<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_tool_assignments', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('finding_count');
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete()->after('returned_at');
            $table->text('return_notes')->nullable()->after('returned_by');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_tool_assignments', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropColumn(['returned_at', 'returned_by', 'return_notes']);
        });
    }
};
