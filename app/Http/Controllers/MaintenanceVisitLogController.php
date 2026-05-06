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
            $doneVisits  = $schedule->where('status', 'completed')->count();

            $scopedFindings         = $this->resolveScopedFindings($inspection, $inspection->pharFindings);
            $totalScopedFindings    = $scopedFindings->count();

            // A finding is resolved only when explicitly marked via "Mark Complete"
            $completedFindingIds = collect($inspection->completed_finding_ids ?? [])
                ->map(fn($id) => (int) $id)->unique()->values();

            $resolvedScopedFindings = $scopedFindings
                ->filter(fn($f) => $completedFindingIds->contains((int) $f->id))
                ->count();

            // Per-finding progress: (unique logged dates / total scheduled dates) * 100
            // For the index card we use the average across all findings
            $scheduledDates = [];
            foreach ($inspection->work_schedule ?? [] as $visit) {
                foreach ($visit['deliverables'] ?? [] as $dl) {
                    if (!empty($dl['date'])) $scheduledDates[] = $dl['date'];
                }
                if (empty($visit['deliverables'] ?? []) && !empty($visit['date'])) {
                    $scheduledDates[] = $visit['date'];
                }
            }
            $totalScheduledDates = count(array_unique($scheduledDates));

            if ($totalScopedFindings > 0 && $totalScheduledDates > 0) {
                $sumPct = 0;
                foreach ($scopedFindings as $finding) {
                    if ($completedFindingIds->contains((int) $finding->id)) {
                        $sumPct += 100;
                        continue;
                    }
                    $loggedDates = $inspection->maintenanceVisitLogs
                        ->where('finding_id', (int) $finding->id)
                        ->pluck('visit_date')
                        ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                        ->unique()->count();
                    $sumPct += min(99, (int) round(($loggedDates / $totalScheduledDates) * 100));
                }
                $findingProgressPct = (int) round($sumPct / $totalScopedFindings);
            } elseif ($totalScopedFindings > 0) {
                $findingProgressPct = $resolvedScopedFindings > 0
                    ? (int) round(($resolvedScopedFindings / $totalScopedFindings) * 100)
                    : 0;
            } else {
                $findingProgressPct = $totalVisits > 0
                    ? (int) round(($doneVisits / $totalVisits) * 100)
                    : 0;
            }

            $progressPct = $findingProgressPct;

            $inspection->setAttribute('maintenance_total_visits', $totalVisits);
            $inspection->setAttribute('maintenance_done_visits', $doneVisits);
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

        // Flatten all scheduled deliverable dates for progress denominator
        $scheduledDates = [];
        foreach ($inspection->work_schedule ?? [] as $visit) {
            foreach ($visit['deliverables'] ?? [] as $dl) {
                if (!empty($dl['date'])) $scheduledDates[] = $dl['date'];
            }
            if (empty($visit['deliverables'] ?? []) && !empty($visit['date'])) {
                $scheduledDates[] = $visit['date'];
            }
        }
        $scheduledDates = array_unique($scheduledDates);
        $totalScheduledDates = count($scheduledDates);

        // Explicitly completed finding IDs (set via "Mark Complete" button)
        $completedFindingIds = collect($inspection->completed_finding_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $logsByDate      = $inspection->maintenanceVisitLogs->groupBy(fn($l) => $l->visit_date->toDateString());
        $toolAssignments = $inspection->toolAssignments;

        return view('admin.maintenance-visit-logs.show', compact(
            'inspection', 'schedule', 'findings', 'logsByDate', 'toolAssignments',
            'completedFindingIds', 'totalScheduledDates'
        ));
    }

    /**
     * Store a new visit log entry (with optional after-photo uploads).
     */
    public function store(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        $dailyHourCap = 11.0;

        if ($user->hasRole('Inspector') && $inspection->inspector_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'visit_date'              => 'required|date',
            'finding_id'              => 'nullable|exists:phar_findings,id',
            'hours_worked'            => 'nullable|numeric|min:0|max:24',
            'status'                  => 'required|in:pending,in_progress,completed',
            'notes'                   => 'nullable|string|max:1000',
            'after_photos'            => 'nullable|array|max:10',
            'after_photos.*'          => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'task_logs'               => 'nullable|array|max:30',
            'task_logs.*.task'        => 'required_with:task_logs|string|max:500',
            'task_logs.*.description' => 'nullable|string|max:2000',
        ]);

        $visitDate = (string) $validated['visit_date'];
        $newHours = (float) ($validated['hours_worked'] ?? 0);

        $existingHoursForDate = (float) MaintenanceVisitLog::query()
            ->where('inspection_id', $inspection->id)
            ->whereDate('visit_date', $visitDate)
            ->sum('hours_worked');

        if ($existingHoursForDate >= $dailyHourCap) {
            return back()
                ->withInput()
                ->with('error', 'This visit date has already reached the 11-hour limit and cannot be used for more logs.');
        }

        if (($existingHoursForDate + $newHours) > $dailyHourCap) {
            $remaining = max(0, $dailyHourCap - $existingHoursForDate);
            return back()
                ->withInput()
                ->withErrors([
                    'hours_worked' => 'Hours exceed the 11-hour cap for this visit date. Remaining allowed: ' . rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') . 'h.',
                ]);
        }

        $afterPaths = [];
        $uploadDisk = config('filesystems.default', 'public');
        if ($request->hasFile('after_photos')) {
            foreach ($request->file('after_photos') as $file) {
                $path = $file->store('maintenance-visit-logs/' . $inspection->id, $uploadDisk);
                $afterPaths[] = $path;
            }
        }

        $taskLogs = collect($validated['task_logs'] ?? [])->filter(function ($tl) {
            return !empty(trim($tl['task'] ?? ''));
        })->map(function ($tl) {
            return [
                'task'        => trim($tl['task']),
                'description' => trim($tl['description'] ?? ''),
            ];
        })->values()->all();

        // Auto-build work_description summary for backward compatibility
        $workDescription = collect($taskLogs)->map(function ($tl) {
            return $tl['task'] . ($tl['description'] ? ': ' . $tl['description'] : '');
        })->implode(' | ');
        if (empty($workDescription)) {
            $workDescription = 'Work logged on this visit date.';
        }

        $log = MaintenanceVisitLog::create([
            'inspection_id'      => $inspection->id,
            'visit_date'         => $validated['visit_date'],
            'finding_id'         => $validated['finding_id'] ?? null,
            'logged_by'          => $user->id,
            'work_description'   => $workDescription,
            'hours_worked'       => $validated['hours_worked'] ?? null,
            'status'             => $validated['status'],
            'notes'              => $validated['notes'] ?? null,
            'after_photos'       => $afterPaths ?: null,
            'accomplished_tasks' => $taskLogs ?: null,
        ]);

        // Update the visit status in work_schedule without prematurely closing the day.
        // A visit date becomes completed only when every finding has a completed log on that date.
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

    /**
     * Explicitly mark a specific finding as 100% completed for this inspection.
     * This is the only way a finding reaches "resolved" status — not from log status alone.
     */
    public function completeFinding(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if ($user->hasRole('Inspector') && $inspection->inspector_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'finding_id' => 'required|exists:phar_findings,id',
        ]);

        $findingId = (int) $validated['finding_id'];

        // Guard: logs must cover at least 75% of scheduled visit days for this finding.
        $scheduledDates = [];
        foreach ($inspection->work_schedule ?? [] as $visit) {
            foreach ($visit['deliverables'] ?? [] as $dl) {
                if (!empty($dl['date'])) $scheduledDates[] = $dl['date'];
            }
            if (empty($visit['deliverables'] ?? []) && !empty($visit['date'])) {
                $scheduledDates[] = $visit['date'];
            }
        }
        $scheduledDates = array_unique($scheduledDates);
        $totalDays = count($scheduledDates);

        $loggedDays = MaintenanceVisitLog::query()
            ->where('inspection_id', $inspection->id)
            ->where('finding_id', $findingId)
            ->when(!empty($scheduledDates), fn($q) => $q->whereIn('visit_date', $scheduledDates))
            ->distinct()
            ->pluck('visit_date')
            ->count();

        $coveragePct = $totalDays > 0 ? ($loggedDays / $totalDays) * 100 : ($loggedDays > 0 ? 100 : 0);

        if ($coveragePct < 75) {
            $needed = $totalDays > 0 ? (int) ceil($totalDays * 0.75) : 1;
            return back()->with('error',
                "Cannot mark this issue as resolved — only {$loggedDays} of {$totalDays} scheduled day(s) have been logged " .
                "(" . round($coveragePct) . "%). At least {$needed} day(s) (75%) must be logged first."
            );
        }

        $existing = collect($inspection->completed_finding_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if (!$existing->contains($findingId)) {
            $inspection->update([
                'completed_finding_ids' => $existing->push($findingId)->all(),
            ]);
        }

        return back()->with('success', 'Issue marked as fully resolved.');
    }

    /**
     * Mark an entire inspection/project as fully complete.
     * Sets all scoped findings as completed.
     */
    public function completeProject(Request $request, Inspection $inspection)
    {
        $user = Auth::user();
        if ($user->hasRole('Inspector') && $inspection->inspector_id !== $user->id) {
            abort(403);
        }

        $findings = $this->resolveScopedFindings($inspection, $inspection->pharFindings()->get());

        // Guard: every scoped finding must have logs on at least 75% of scheduled days.
        $scheduledDates = [];
        foreach ($inspection->work_schedule ?? [] as $visit) {
            foreach ($visit['deliverables'] ?? [] as $dl) {
                if (!empty($dl['date'])) $scheduledDates[] = $dl['date'];
            }
            if (empty($visit['deliverables'] ?? []) && !empty($visit['date'])) {
                $scheduledDates[] = $visit['date'];
            }
        }
        $scheduledDates = array_unique($scheduledDates);
        $totalDays = count($scheduledDates);
        $neededDays = $totalDays > 0 ? (int) ceil($totalDays * 0.75) : 1;

        // Fetch logged days per finding in one query
        $loggedDaysPerFinding = MaintenanceVisitLog::query()
            ->where('inspection_id', $inspection->id)
            ->whereNotNull('finding_id')
            ->when(!empty($scheduledDates), fn($q) => $q->whereIn('visit_date', $scheduledDates))
            ->selectRaw('finding_id, COUNT(DISTINCT visit_date) as day_count')
            ->groupBy('finding_id')
            ->pluck('day_count', 'finding_id')
            ->mapWithKeys(fn($count, $id) => [(int) $id => (int) $count]);

        $belowThreshold = $findings->filter(function ($f) use ($loggedDaysPerFinding, $totalDays) {
            $logged = $loggedDaysPerFinding->get((int) $f->id, 0);
            $pct    = $totalDays > 0 ? ($logged / $totalDays) * 100 : ($logged > 0 ? 100 : 0);
            return $pct < 75;
        });

        if ($belowThreshold->isNotEmpty()) {
            $labels = $belowThreshold->take(3)->map(function ($f) use ($loggedDaysPerFinding, $totalDays) {
                $logged = $loggedDaysPerFinding->get((int) $f->id, 0);
                $pct    = $totalDays > 0 ? round(($logged / $totalDays) * 100) : 0;
                $title  = \Illuminate\Support\Str::limit($f->task_question ?? 'Finding #'.$f->id, 40);
                return '"' . $title . '" (' . $pct . '%)';
            })->implode(', ');
            $extra = $belowThreshold->count() > 3 ? ' and ' . ($belowThreshold->count() - 3) . ' more' : '';
            return back()->with('error',
                "Cannot complete the project — the following issues are below 75% log coverage: {$labels}{$extra}. " .
                "Each issue needs logs on at least {$neededDays} of {$totalDays} scheduled day(s)."
            );
        }

        $allIds = $findings->pluck('id')->map(fn($id) => (int) $id)->unique()->values()->all();

        $inspection->update(['completed_finding_ids' => $allIds]);

        // Sync the parent Project status to 'completed'
        if ($inspection->project_id) {
            \App\Models\Project::where('id', $inspection->project_id)->update([
                'status'          => 'completed',
                'actual_end_date' => now()->toDateString(),
            ]);
        }

        return back()->with('success', 'Project marked as fully complete. All issues resolved.');
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
