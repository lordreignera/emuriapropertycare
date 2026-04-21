<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\MaintenanceVisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaintenanceVisitLogController extends Controller
{
    /**
     * List all inspections that have a work schedule set.
     */
    public function index()
    {
        $user = Auth::user();

        $query = Inspection::with(['property', 'maintenanceVisitLogs'])
            ->whereNotNull('etogo_signed_at');

        // Inspectors see only their assigned inspections
        if ($user->hasRole('Inspector')) {
            $query->where('inspector_id', $user->id);
        }

        $inspections = $query->orderBy('planned_start_date')->get();

        return view('admin.maintenance-visit-logs.index', compact('inspections'));
    }

    /**
     * Show the visit log for a specific inspection — before/after photos per visit.
     */
    public function show(Inspection $inspection)
    {
        $user = Auth::user();

        if ($user->hasRole('Inspector') && $inspection->inspector_id !== $user->id) {
            abort(403);
        }

        $inspection->load(['property', 'pharFindings', 'maintenanceVisitLogs.loggedBy', 'maintenanceVisitLogs.finding', 'toolAssignments.toolSetting', 'toolAssignments.returnedBy']);

        $schedule   = collect($inspection->work_schedule ?? []);
        $findings   = $inspection->pharFindings;
        $logsByDate = $inspection->maintenanceVisitLogs->groupBy(fn($l) => $l->visit_date->toDateString());
        $toolAssignments = $inspection->toolAssignments;

        return view('admin.maintenance-visit-logs.show', compact('inspection', 'schedule', 'findings', 'logsByDate', 'toolAssignments'));
    }

    /**
     * Store a new visit log entry (with optional after-photo uploads).
     */
    public function store(Request $request, Inspection $inspection)
    {
        $user = Auth::user();

        if ($user->hasRole('Inspector') && $inspection->inspector_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'visit_date'       => 'required|date',
            'finding_id'       => 'nullable|exists:phar_findings,id',
            'work_description' => 'required|string|max:2000',
            'hours_worked'     => 'nullable|numeric|min:0|max:24',
            'status'           => 'required|in:pending,in_progress,completed',
            'notes'            => 'nullable|string|max:1000',
            'after_photos'     => 'nullable|array|max:10',
            'after_photos.*'   => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $afterPaths = [];
        if ($request->hasFile('after_photos')) {
            foreach ($request->file('after_photos') as $file) {
                $path = $file->store('maintenance-visit-logs/' . $inspection->id, 'public');
                $afterPaths[] = $path;
            }
        }

        $log = MaintenanceVisitLog::create([
            'inspection_id'    => $inspection->id,
            'visit_date'       => $validated['visit_date'],
            'finding_id'       => $validated['finding_id'] ?? null,
            'logged_by'        => $user->id,
            'work_description' => $validated['work_description'],
            'hours_worked'     => $validated['hours_worked'] ?? null,
            'status'           => $validated['status'],
            'notes'            => $validated['notes'] ?? null,
            'after_photos'     => $afterPaths ?: null,
        ]);

        // Update the visit status in the work_schedule JSON
        $schedule = collect($inspection->work_schedule ?? [])->map(function ($visit) use ($validated, $log) {
            if ($visit['date'] === $validated['visit_date'] && $validated['status'] === 'completed') {
                $visit['status'] = 'completed';
            }
            return $visit;
        })->all();

        $inspection->update(['work_schedule' => $schedule]);

        return back()->with('success', 'Visit log entry saved successfully.');
    }
}
