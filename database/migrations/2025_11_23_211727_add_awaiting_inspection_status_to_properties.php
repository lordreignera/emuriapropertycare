<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN status ENUM('pending_approval', 'approved', 'rejected', 'awaiting_inspection') DEFAULT 'pending_approval'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE properties MODIFY COLUMN status ENUM('pending_approval', 'approved', 'rejected') DEFAULT 'pending_approval'");
    }
};
