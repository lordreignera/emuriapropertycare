<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->json('recommended_actions')->nullable()->after('description');
        });

        Schema::table('subsystems', function (Blueprint $table) {
            $table->json('recommended_actions')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('subsystems', function (Blueprint $table) {
            $table->dropColumn('recommended_actions');
        });

        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('recommended_actions');
        });
    }
};
