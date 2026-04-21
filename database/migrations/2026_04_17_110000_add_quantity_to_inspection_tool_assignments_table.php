<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_tool_assignments', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->default(1)->after('tool_name');
        });
    }

    public function down(): void
    {
        Schema::table('inspection_tool_assignments', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
