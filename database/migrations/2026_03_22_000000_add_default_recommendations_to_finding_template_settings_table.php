<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finding_template_settings', function (Blueprint $table) {
            $table->json('default_recommendations')->nullable()->after('default_notes');
        });
    }

    public function down(): void
    {
        Schema::table('finding_template_settings', function (Blueprint $table) {
            $table->dropColumn('default_recommendations');
        });
    }
};
