<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('tool_settings', 'quantity')) {
                $table->unsignedSmallInteger('quantity')->default(1)->after('tool_name')
                    ->comment('Number of units of this tool (e.g. 3 owned ladders)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tool_settings', function (Blueprint $table) {
            if (Schema::hasColumn('tool_settings', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
