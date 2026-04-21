<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // All existing quantities were auto-populated from tool stock by the old
        // syncToolAssignments() code — not manually assigned by an admin/PM.
        // Reset them to 0 so admins can assign the correct quantities going forward.
        DB::table('inspection_tool_assignments')->update(['quantity' => 0]);
    }

    public function down(): void
    {
        // Cannot restore original auto-populated values.
    }
};
