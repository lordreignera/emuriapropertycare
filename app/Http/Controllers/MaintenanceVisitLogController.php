<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionQuotation;
use App\Models\MaintenanceVisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        $query = Inspection::with(['property', 'maintenanceVisitLogs', 'pharFindings'])
            ->whereNotNull('etogo_signed_at');

        $progressFilter = (string) request('progress_filter', 'all');

        // Inspectors see only their assigned inspections
        if ($user->hasRole('Inspector')) {
            $query->where('inspector_id', $user->id);
        }

        $inspections = $query->orderBy('planned_start_date')->get();

        // Dashboard card progress should reflect real work completion from logs,
        // not only work_schedule status toggles.
        $inspections->each(function (Inspection $inspection) {
            $schedule = collect($inspection->work_schedule ?? []);
            $totalVisits = $schedule->count();
            $doneVisits = $schedule->where('status', 'completed')->count();
            $scheduleProgressPct = $totalVisits > 0
                ? (int) round(($doneVisits / $totalVisits) * 100)
                : 0;

            $scopedFindings = $this->resolveScopedFindings($inspection, $inspection->pharFindings);
            $totalScopedFindings = $scopedFindings->count();

            $resolvedScopedFindings = $scopedFindings
                ->filter(function ($finding) use ($inspection) {
                    return $inspection->maintenanceVisitLogs
                        ->where('finding_id', (int) $finding->id)
                        ->where('status', 'completed')
                        ->isNotEmpty();
                })
                ->count();

            $findingProgressPct = $totalScopedFindings > 0
                ? (int) round(($resolvedScopedFindings / $totalScopedFindings) * 100)
                : 0;

            $progressPct = max($scheduleProgressPct, $findingProgressPct);

            // Keep the card counters consistent with computed progress.
            // For legacy rows where work_schedule status was not flipped to completed,
            // ensure "Completed" count does not remain 0 when progress reached 100%.
            $displayDoneVisits = $doneVisits;
            if ($progressPct >= 100 && $totalVisits > 0) {
                $displayDoneVisits = $totalVisits;
            }

            $inspection->setAttribute('maintenance_total_visits', $totalVisits);
            $inspection->setAttribute('maintenance_done_visits', $displayDoneVisits);
            $inspection->setAttribute('maintenance_total_findings', $totalScopedFindings);
            $inspection->setAttribute('maintenance_resolved_findings', $resolvedScopedFindings);
            $inspection->setAttribute('maintenance_progress_pct', $progressPct);
            $inspection->setAttribute('maintenance_progress_status', $progressPct >= 100 ? 'completed' : ($progressPct > 0 ? 'in_progress' : 'not_started'));
        });

        if ($progressFilter !== 'all') {
            $inspections = $inspections->filter(function (Inspection $inspection) use ($progressFilter) {
                $progress = (int) ($inspection->maintenance_progress_pct ?? 0);
                return match ($progressFilter) {
                    'active'      => $progress < 100,
                    'completed'   => $progress >= 100,
                    'in_progress' => $progress > 0 && $progress < 100,
                    'at_least_50' => $progress >= 50,
                    default       => true,
                };
            })->values();
        }

        return view('admin.maintenance-visit-logs.index', compact('inspections', 'progressFilter'));
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
        $findings   = $this->resolveScopedFindings($inspection, $inspection->pharFindings);

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
        $uploadDisk = config('filesystems.default', 'public');
        if ($request->hasFile('after_photos')) {
            foreach ($request->file('after_photos') as $file) {
                $path = $file->store('maintenance-visit-logs/' . $inspection->id, $uploadDisk);
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

        // Update the visit status in work_schedule without prematurely closing the day.
        // A visit date becomes completed only when every finding has a completed log on that date.
        $visitDate = (string) $validated['visit_date'];
        $scopedFindings = $this->resolveScopedFindings($inspection, $inspection->pharFindings()->get());
        $allFindingIds = $scopedFindings->pluck('id')->map(fn($id) => (int) $id)->values();

        $logsForDate = MaintenanceVisitLog::query()
            ->where('inspection_id', $inspection->id)
            ->whereDate('visit_date', $visitDate)
            ->get(['finding_id', 'status']);

        $completedFindingIdsForDate = $logsForDate
            ->where('status', 'completed')
            ->pluck('finding_id')
            ->filter(fn($id) => !is_null($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $hasAnyLogsForDate = $logsForDate->isNotEmpty();
        $visitCanBeClosed = $allFindingIds->isNotEmpty()
            && $allFindingIds->every(fn($findingId) => $completedFindingIdsForDate->contains((int) $findingId));

        $schedule = collect($inspection->work_schedule ?? [])->map(function ($visit) use ($visitDate, $visitCanBeClosed, $hasAnyLogsForDate) {
            if (($visit['date'] ?? null) === $visitDate) {
                if ($visitCanBeClosed) {
                    $visit['status'] = 'completed';
                } elseif ($hasAnyLogsForDate) {
                    $visit['status'] = 'in_progress';
                }
            }
            return $visit;
        })->all();

        $inspection->update(['work_schedule' => $schedule]);

        return back()->with('success', 'Visit log entry saved successfully.');
    }

    private function resolveScopedFindings(Inspection $inspection, Collection $findings): Collection
    {
        $activeQuotation = null;
        if (!empty($inspection->active_quotation_id)) {
            $activeQuotation = InspectionQuotation::query()
                ->where('id', $inspection->active_quotation_id)
                ->where('inspection_id', $inspection->id)
                ->first();
        }

        if (($activeQuotation?->status ?? null) !== 'approved') {
            $approvedQuotation = InspectionQuotation::query()
                ->where('inspection_id', $inspection->id)
                ->where('status', 'approved')
                ->orderBy('id', 'desc')
                ->first();
            if ($approvedQuotation) {
                $activeQuotation = $approvedQuotation;
            }
        }

        if (($activeQuotation?->status ?? null) !== 'approved') {
            return $findings->values();
        }

        $approvedIds = collect($activeQuotation->approved_finding_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values();

        if ($approvedIds->isEmpty()) {
            return $findings->values();
        }

        $filteredById = $findings
            ->filter(fn($f) => $approvedIds->contains((int) ($f->id ?? 0)))
            ->values();

        if ($filteredById->isNotEmpty()) {
            return $filteredById;
        }

        $makeFindingKey = function ($issueOrTask, $category) {
            $left = strtolower(trim((string) $issueOrTask));
            $right = strtolower(trim((string) $category));
            return $left . '|' . $right;
        };

        $snapshot = collect($activeQuotation->findings_snapshot ?? [])->values();
        $approvedScopeKeys = $snapshot
            ->filter(fn($f) => $approvedIds->contains((int) ($f['id'] ?? 0)))
            ->map(fn($f) => $makeFindingKey(
                $f['task_question'] ?? ($f['issue'] ?? ''),
                $f['category'] ?? ''
            ))
            ->filter(fn($k) => $k !== '|')
            ->unique()
            ->values();

        if ($approvedScopeKeys->isEmpty()) {
            return $findings->values();
        }

        return $findings
            ->filter(function ($f) use ($approvedScopeKeys, $makeFindingKey) {
                $key = $makeFindingKey(
                    $f->task_question ?? '',
                    $f->category ?? ''
                );
                return $approvedScopeKeys->contains($key);
            })
            ->values();
    }
}
