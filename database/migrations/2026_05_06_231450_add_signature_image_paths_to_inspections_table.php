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
        Schema::table('inspections', function (Blueprint $table) {
            // Snapshot of the signer's uploaded signature image at time of signing
            $table->string('client_signature_image_path')->nullable()->after('client_signature');
            $table->string('etogo_signature_image_path')->nullable()->after('etogo_signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['client_signature_image_path', 'etogo_signature_image_path']);
        });
    }
};
