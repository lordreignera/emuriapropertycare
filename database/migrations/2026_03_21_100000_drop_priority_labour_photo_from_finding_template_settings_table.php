<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finding_template_settings', function (Blueprint $table) {
            $table->dropColumn(['default_priority', 'default_labour_hours', 'photo_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('finding_template_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('default_priority')->default(2)->after('category');
            $table->decimal('default_labour_hours', 8, 2)->default(0)->after('default_included');
            $table->string('photo_reference', 50)->nullable()->after('default_labour_hours');
        });
    }
};
