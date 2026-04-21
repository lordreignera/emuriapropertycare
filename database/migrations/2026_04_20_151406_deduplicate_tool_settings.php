<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Before the route-parameter fix every "edit" on a ToolSetting was
     * silently INSERTING a new row instead of updating the existing one.
     * This migration:
     *  1. Groups ToolSettings by (tool_name, system_id, subsystem_id).
     *  2. Keeps the row with the HIGHEST id (the most-recent edit/intended state).
     *  3. Re-points any InspectionToolAssignment rows that referenced a duplicate
     *     to the kept row so no deployment data is lost.
     *  4. Deletes the surplus duplicate rows.
     */
    public function up(): void
    {
        $groups = DB::table('tool_settings')
            ->select('tool_name', 'system_id', 'subsystem_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('tool_name', 'system_id', 'subsystem_id')
            ->get();

        foreach ($groups as $group) {
            // IDs for this duplicate group that are NOT the one we're keeping
            $duplicateIds = DB::table('tool_settings')
                ->where('tool_name', $group->tool_name)
                ->where('system_id', $group->system_id)
                ->where('subsystem_id', $group->subsystem_id)
                ->where('id', '!=', $group->keep_id)
                ->pluck('id');

            if ($duplicateIds->isEmpty()) {
                continue;
            }

            // Re-point any assignments that reference a duplicate to the keeper
            DB::table('inspection_tool_assignments')
                ->whereIn('tool_setting_id', $duplicateIds)
                ->update(['tool_setting_id' => $group->keep_id]);

            // Delete the duplicate tool_settings rows
            DB::table('tool_settings')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Cannot restore deleted duplicates.
    }
};
