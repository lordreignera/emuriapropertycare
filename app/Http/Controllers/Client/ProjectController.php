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
            ->with(['property', 'inspections.maintenanceVisitLogs'])
            ->latest()
            ->get();

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

        $completedLogs = $inspection->maintenanceVisitLogs
            ->where('status', 'completed')
            ->sortByDesc('created_at')
            ->values();

        $completedByFinding = $completedLogs
            ->whereNotNull('finding_id')
            ->groupBy('finding_id');

        return view('client.projects.log-sheet', [
            'project' => $project,
            'inspection' => $inspection,
            'findings' => $findings,
            'completedLogs' => $completedLogs,
            'completedByFinding' => $completedByFinding,
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
