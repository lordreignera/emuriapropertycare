<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionQuotation;
use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $propertyIds = \App\Models\Property::where('user_id', $user->id)->pluck('id');

        $projects = Project::whereIn('property_id', $propertyIds)
            ->with([
                'property',
                'inspections.maintenanceVisitLogs',
                'inspections.pharFindings',
            ])
            ->latest()
            ->get();

        // Compute progress per inspection and attach as attributes
        $projects->each(function ($project) {
            $insp = $project->inspections->sortByDesc('completed_date')->first();
            if (!$insp) return;

            $completedFindingIds = collect($insp->completed_finding_ids ?? [])
                ->map(fn($id) => (int) $id)->unique()->values();

            $scheduledDates = [];
            foreach ($insp->work_schedule ?? [] as $visit) {
                foreach ($visit['deliverables'] ?? [] as $dl) {
                    if (!empty($dl['date'])) $scheduledDates[] = $dl['date'];
                }
                if (empty($visit['deliverables'] ?? []) && !empty($visit['date'])) {
                    $scheduledDates[] = $visit['date'];
                }
            }
            $totalScheduledDates = count(array_unique($scheduledDates));

            // Use approved-quotation scope (same as show/log-sheet pages)
            $findings = $this->resolveScopedFindings($insp, $insp->pharFindings);
            $totalFindings = $findings->count();
            $resolvedCount = 0;
            $sumPct = 0;
            foreach ($findings as $f) {
                if ($completedFindingIds->contains((int) $f->id)) {
                    $sumPct += 100;
                    $resolvedCount++;
                } elseif ($totalScheduledDates > 0) {
                    $loggedDates = $insp->maintenanceVisitLogs
                        ->where('finding_id', (int) $f->id)
                        ->pluck('visit_date')
                        ->map(fn($d) => is_string($d) ? $d : $d->toDateString())
                        ->unique()->count();
                    $sumPct += min(99, (int) round(($loggedDates / $totalScheduledDates) * 100));
                }
            }
            $overallPct = $totalFindings > 0 ? (int) round($sumPct / $totalFindings) : 0;

            $insp->setAttribute('progress_pct', $overallPct);
            $insp->setAttribute('progress_resolved', $resolvedCount);
            $insp->setAttribute('progress_total', $totalFindings);
            $insp->setAttribute('progress_done', $overallPct >= 100);

            // Payment balance data
            $arpTotal   = (float) ($insp->arp_total_locked ?? $insp->trc_annual ?? 0);
            $instAmt    = (float) ($insp->installment_amount ?? 0);
            $instPaid   = (int)   ($insp->installments_paid ?? 0);
            $payPlan    = $insp->payment_plan ?? 'full';
            $fullyPaid  = $insp->arp_fully_paid_at !== null;
            $paidSoFar  = match(true) {
                $fullyPaid                                       => $arpTotal,
                in_array($payPlan, ['per_visit', 'installment']) => $instAmt * $instPaid,
                ($insp->work_payment_status ?? '') === 'paid'    => (float) ($insp->work_payment_amount ?? $arpTotal),
                default                                          => 0.0,
            };
            $balance = $arpTotal > 0 ? max(0.0, $arpTotal - $paidSoFar) : 0.0;
            $insp->setAttribute('payment_total_cost', $arpTotal);
            $insp->setAttribute('payment_paid_so_far', round($paidSoFar, 2));
            $insp->setAttribute('payment_balance', round($balance, 2));
            $insp->setAttribute('payment_has_balance', $balance > 0.01);
        });

        return view('client.projects.index', compact('projects'));
    }

    public function showCompletedLogSheet(Project $project, Inspection $inspection)
    {
        $user = auth()->user();

        if ((int) ($project->property?->user_id ?? 0) !== (int) $user->id) {
            abort(403);
        }

        if ((int) $inspection->project_id !== (int) $project->id) {
            abort(404);
        }

        $inspection->load([
            'property',
            'pharFindings',
            'maintenanceVisitLogs.loggedBy',
            'maintenanceVisitLogs.finding',
        ]);

        $findings = $this->resolveScopedFindings($inspection, $inspection->pharFindings)
            ->values();

        // All log entries (all statuses) for progress calculation
        $allLogsByFinding = $inspection->maintenanceVisitLogs
            ->whereNotNull('finding_id')
            ->groupBy('finding_id');

        $completedLogs = $inspection->maintenanceVisitLogs
            ->where('status', 'completed')
            ->sortByDesc('created_at')
            ->values();

        $completedByFinding = $completedLogs
            ->whereNotNull('finding_id')
            ->groupBy('finding_id');

        // Explicitly resolved findings
        $completedFindingIds = collect($inspection->completed_finding_ids ?? [])
            ->map(fn($id) => (int) $id)->unique()->values();

        // Total scheduled dates (denominator for progress %)
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

        return view('client.projects.log-sheet', [
            'project'             => $project,
            'inspection'          => $inspection,
            'findings'            => $findings,
            'completedLogs'       => $completedLogs,
            'completedByFinding'  => $completedByFinding,
            'allLogsByFinding'    => $allLogsByFinding,
            'completedFindingIds' => $completedFindingIds,
            'totalScheduledDates' => $totalScheduledDates,
        ]);
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
