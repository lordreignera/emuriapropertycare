<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_pricing', function (Blueprint $table) {
            $table->foreign('property_type_id')
                ->references('id')
                ->on('property_types')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('package_pricing', function (Blueprint $table) {
            $table->dropForeign(['property_type_id']);
        });
    }
};
