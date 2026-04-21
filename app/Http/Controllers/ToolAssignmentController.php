<?php

namespace App\Http\Controllers;

use App\Models\InspectionToolAssignment;
use App\Models\ToolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolAssignmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Only admins/PMs may access this page
        if (! $user->hasRole(['Super Admin', 'Administrator', 'Project Manager'])) {
            abort(403);
        }

        // Load all inspections that both parties have signed (client + etogo)
        $assignments = InspectionToolAssignment::with([
                'inspection.property',
                'toolSetting',
                'returnedBy',
            ])
            ->whereHas('inspection', fn($q) => $q
                ->whereNotNull('client_signature')
                ->whereNotNull('etogo_signed_at')
            )
            ->orderByRaw('returned_at IS NULL DESC')
            ->orderBy('created_at', 'desc')
            ->get();

        $unreturnedCount = $assignments->whereNull('returned_at')->where('quantity', '>', 0)->count();

        // Pre-compute deployed quantities per tool_setting_id in a single query
        // so the blade view does not run N inline DB queries
        $deployedByTool = InspectionToolAssignment::query()
            ->whereNull('returned_at')
            ->where('quantity', '>', 0)
            ->selectRaw('tool_setting_id, SUM(quantity) as total_deployed')
            ->groupBy('tool_setting_id')
            ->pluck('total_deployed', 'tool_setting_id');

        return view('admin.tool-assignments.index', compact('assignments', 'unreturnedCount', 'deployedByTool'));
    }

    /**
     * Admin/PM sets the quantity to deploy for a specific assignment.
     */
    public function assignQuantity(Request $request, InspectionToolAssignment $assignment)
    {
        $user = Auth::user();
        if (! $user->hasRole(['Super Admin', 'Administrator', 'Project Manager'])) {
            abort(403);
        }

        $toolSetting = $assignment->toolSetting;
        $maxAllowed  = $toolSetting ? $toolSetting->remainingQuantity() + (int) $assignment->quantity : 999;

        $validated = $request->validate([
            'quantity'     => "required|integer|min:0|max:{$maxAllowed}",
            'assign_notes' => 'nullable|string|max:500',
        ]);

        $newQty = (int) $validated['quantity'];

        $assignment->update([
            'quantity'     => $newQty,
            'assign_notes' => $validated['assign_notes'] ?? null,
        ]);

        // Keep ToolSetting availability_status in sync (available vs non_available)
        if ($toolSetting) {
            $totalDeployed = InspectionToolAssignment::where('tool_setting_id', $toolSetting->id)
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->sum('quantity');

            // non_available when all stock is out, available when some remains
            $newStatus = $totalDeployed >= (int) $toolSetting->quantity ? 'non_available' : 'available';
            $toolSetting->update(['availability_status' => $newStatus]);
        }

        $label = $assignment->tool_name;
        return back()->with('success', "Assigned {$newQty} unit(s) of '{$label}' to this project.");
    }

    public function markReturned(Request $request, InspectionToolAssignment $assignment)
    {
        $validated = $request->validate([
            'return_notes' => 'nullable|string|max:500',
        ]);

        if ($assignment->isReturned()) {
            return back()->with('error', 'This tool has already been marked as returned.');
        }

        $assignment->update([
            'returned_at'  => now(),
            'returned_by'  => Auth::id(),
            'return_notes' => $validated['return_notes'] ?? null,
        ]);

        // If the tool setting has no more unreturned active assignments, mark it available
        if ($assignment->toolSetting) {
            $stillOut = InspectionToolAssignment::where('tool_setting_id', $assignment->tool_setting_id)
                ->whereNull('returned_at')
                ->where('quantity', '>', 0)
                ->exists();

            if (! $stillOut) {
                $assignment->toolSetting->update(['availability_status' => 'available']);
            }
        }

        return back()->with('success', "'{$assignment->tool_name}' marked as returned successfully.");
    }
}
