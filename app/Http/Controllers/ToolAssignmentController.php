<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\FindingTemplateSetting;
use App\Models\InspectionSystem;
use App\Models\InspectionSubsystem;
use App\Models\InspectionToolAssignment;
use App\Models\ToolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolAssignmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Only Super Admin / Store Manager may access this page
        if (! $user->hasRole(['Super Admin', 'Store Manager'])) {
            abort(403);
        }

        $search = trim((string) request('q', ''));

        // Load all assignments for projects where the client has signed and payment is confirmed.
        $assignmentsQuery = InspectionToolAssignment::with([
                'inspection.property',
                'inspection.project',
                'toolSetting',
                'returnedBy',
            ])
            ->whereHas('inspection', fn($q) => $q
                ->whereNotNull('client_signature')
            ->where('work_payment_status', 'paid')
            );

        if ($search !== '') {
            $assignmentsQuery->where(function ($q) use ($search) {
                $q->whereHas('inspection', function ($iq) use ($search) {
                    $iq->where('property_name', 'like', "%{$search}%")
                        ->orWhereHas('project', function ($pq) use ($search) {
                            $pq->where('project_number', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%");
                        })
                        ->orWhereHas('property', function ($prq) use ($search) {
                            $prq->where('property_name', 'like', "%{$search}%")
                                ->orWhere('property_code', 'like', "%{$search}%")
                                ->orWhere('property_address', 'like', "%{$search}%");
                        });
                })
                ->orWhere('tool_name', 'like', "%{$search}%");
            });
        }

        $assignments = $assignmentsQuery
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

        $eligibleInspections = Inspection::query()
            ->with([
                'property:id,property_name,property_address',
                'project:id,project_number,title',
                'pharFindings:id,inspection_id,task_question,category,included_yn',
            ])
            ->whereNotNull('client_signature')
            ->where('work_payment_status', 'paid')
            ->whereNull('etogo_signed_at')
            ->orderByDesc('updated_at')
            ->get(['id', 'project_id', 'property_id', 'property_name', 'status', 'work_schedule', 'updated_at']);

        $activeTools = ToolSetting::query()
            ->where('is_active', true)
            ->orderBy('tool_name')
            ->get(['id', 'tool_name', 'quantity', 'ownership_status', 'availability_status', 'system_id', 'subsystem_id', 'finding_template_setting_id']);

        // Build recommendation map: inspection_id => [tool_setting_id, ...]
        //
        // Three match paths (most specific → broadest):
        //   Path 1 — task_question exact match → FindingTemplateSetting.id → ToolSetting.finding_template_setting_id
        //   Path 2 — phar_finding.category → FindingTemplateSetting.category → system/subsystem → ToolSetting
        //   Path 3 — phar_finding.category name-compared to systems.name / subsystems.name → ToolSetting
        $toolRecommendationsByInspection = [];

        // Collect all unique questions and categories across eligible inspections (for batch DB queries)
        $allIncludedFindings = $eligibleInspections
            ->flatMap(fn($insp) => $insp->pharFindings->where('included_yn', true));

        $allQuestions = $allIncludedFindings
            ->pluck('task_question')
            ->filter(fn($q) => trim((string) $q) !== '')
            ->map(fn($q) => trim((string) $q))
            ->unique()
            ->values();

        $allCategories = $allIncludedFindings
            ->pluck('category')
            ->filter(fn($c) => trim((string) $c) !== '')
            ->map(fn($c) => trim((string) $c))
            ->unique()
            ->values();

        // Path 1 + 2: Look up templates by task_question and by category in one pass
        $templatesByQuestion = FindingTemplateSetting::query()
            ->when($allQuestions->isNotEmpty(), fn($q) => $q->whereIn('task_question', $allQuestions))
            ->when($allQuestions->isEmpty(), fn($q) => $q->whereRaw('1=0'))
            ->get(['id', 'task_question', 'category', 'system_id', 'subsystem_id'])
            ->groupBy(fn($tpl) => trim((string) $tpl->task_question));

        $templatesByCategory = FindingTemplateSetting::query()
            ->when($allCategories->isNotEmpty(), fn($q) => $q->whereIn('category', $allCategories))
            ->when($allCategories->isEmpty(), fn($q) => $q->whereRaw('1=0'))
            ->get(['id', 'category', 'system_id', 'subsystem_id'])
            ->groupBy(fn($tpl) => trim((string) $tpl->category));

        // Path 3: Match category names directly against systems/subsystems names
        $systemsByName = InspectionSystem::query()
            ->when($allCategories->isNotEmpty(), fn($q) => $q->whereIn('name', $allCategories))
            ->when($allCategories->isEmpty(), fn($q) => $q->whereRaw('1=0'))
            ->get(['id', 'name'])
            ->keyBy(fn($s) => trim((string) $s->name));

        $subsystemsByName = InspectionSubsystem::query()
            ->when($allCategories->isNotEmpty(), fn($q) => $q->whereIn('name', $allCategories))
            ->when($allCategories->isEmpty(), fn($q) => $q->whereRaw('1=0'))
            ->get(['id', 'name', 'system_id'])
            ->keyBy(fn($s) => trim((string) $s->name));

        foreach ($eligibleInspections as $inspection) {
            $includedFindings = $inspection->pharFindings->where('included_yn', true);

            $inspectionQuestions = $includedFindings
                ->pluck('task_question')
                ->filter(fn($q) => trim((string) $q) !== '')
                ->map(fn($q) => trim((string) $q))
                ->unique();

            $inspectionCategories = $includedFindings
                ->pluck('category')
                ->filter(fn($c) => trim((string) $c) !== '')
                ->map(fn($c) => trim((string) $c))
                ->unique();

            // Path 1: templates matched by exact task_question
            $questionTemplates = $inspectionQuestions
                ->flatMap(fn($q) => $templatesByQuestion->get($q, collect()))
                ->unique('id')
                ->values();

            // Path 2: templates matched by category
            $categoryTemplates = $inspectionCategories
                ->flatMap(fn($c) => $templatesByCategory->get($c, collect()))
                ->unique('id')
                ->values();

            // Merge template-derived system/subsystem ids (paths 1 + 2)
            $allMatchedTemplates = $questionTemplates->merge($categoryTemplates)->unique('id');
            $templateIds  = $allMatchedTemplates->pluck('id')->filter()->map(fn($v) => (int) $v)->unique();
            $systemIds    = $allMatchedTemplates->pluck('system_id')->filter()->map(fn($v) => (int) $v)->unique();
            $subsystemIds = $allMatchedTemplates->pluck('subsystem_id')->filter()->map(fn($v) => (int) $v)->unique();

            // Path 3: category name ↔ system/subsystem name
            $nameSystemIds    = $inspectionCategories->map(fn($c) => optional($systemsByName->get($c))->id)->filter()->map(fn($v) => (int) $v)->unique();
            $nameSubsystemIds = $inspectionCategories->map(fn($c) => optional($subsystemsByName->get($c))->id)->filter()->map(fn($v) => (int) $v)->unique();

            $systemIds    = $systemIds->merge($nameSystemIds)->unique();
            $subsystemIds = $subsystemIds->merge($nameSubsystemIds)->unique();

            $recommendedToolIds = $activeTools
                ->filter(function ($tool) use ($templateIds, $systemIds, $subsystemIds) {
                    $templateMatch  = !empty($tool->finding_template_setting_id) && $templateIds->contains((int) $tool->finding_template_setting_id);
                    $subsystemMatch = !empty($tool->subsystem_id) && $subsystemIds->contains((int) $tool->subsystem_id);
                    $systemMatch    = !empty($tool->system_id) && $systemIds->contains((int) $tool->system_id);

                    return $templateMatch || $subsystemMatch || $systemMatch;
                })
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all();

            $toolRecommendationsByInspection[(int) $inspection->id] = $recommendedToolIds;
        }

        $toolsOutUnits = (int) $assignments->whereNull('returned_at')->where('quantity', '>', 0)->sum('quantity');
        $toolsReturnedUnits = (int) $assignments->whereNotNull('returned_at')->sum('quantity');
        $totalToolStockUnits = (int) $activeTools->sum('quantity');
        $toolsInStoreUnits = max(0, $totalToolStockUnits - $toolsOutUnits);

        return view('admin.tool-assignments.index', compact(
            'assignments',
            'search',
            'unreturnedCount',
            'deployedByTool',
            'eligibleInspections',
            'activeTools',
            'toolRecommendationsByInspection',
            'toolsOutUnits',
            'toolsReturnedUnits',
            'toolsInStoreUnits'
        ));
    }

    /**
     * Manually assign a tool to an active inspection/project.
     */
    public function storeManualAssignment(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasRole(['Super Admin', 'Store Manager'])) {
            abort(403);
        }

        $validated = $request->validate([
            'inspection_id' => 'required|exists:inspections,id',
            'tool_setting_id' => 'required|exists:tool_settings,id',
            'quantity' => 'required|integer|min:1',
            'assign_notes' => 'nullable|string|max:500',
        ]);

        $inspection = Inspection::query()
            ->where('id', (int) $validated['inspection_id'])
            ->whereNotNull('client_signature')
            ->where('work_payment_status', 'paid')
            ->whereNull('etogo_signed_at')
            ->first();

        if (! $inspection) {
            return back()->with('error', 'Selected project is not eligible for tool assignment yet.');
        }

        $toolSetting = ToolSetting::query()->where('is_active', true)->find((int) $validated['tool_setting_id']);
        if (! $toolSetting) {
            return back()->with('error', 'Selected tool is not active.');
        }

        $existing = InspectionToolAssignment::query()
            ->where('inspection_id', $inspection->id)
            ->where('tool_name', trim((string) $toolSetting->tool_name))
            ->first();

        $existingActiveQty = ($existing && $existing->returned_at === null) ? (int) $existing->quantity : 0;
        $maxAllowed = $toolSetting->remainingQuantity() + $existingActiveQty;
        $newQty = (int) $validated['quantity'];

        if ($newQty > $maxAllowed) {
            return back()->with('error', "Only {$maxAllowed} unit(s) are currently available for this tool.");
        }

        $payload = [
            'property_id' => $inspection->property_id,
            'tool_setting_id' => $toolSetting->id,
            'system_id' => $toolSetting->system_id,
            'subsystem_id' => $toolSetting->subsystem_id,
            'tool_name' => trim((string) $toolSetting->tool_name),
            'quantity' => $newQty,
            'ownership_status' => $toolSetting->ownership_status,
            'availability_status' => $toolSetting->availability_status,
            'assign_notes' => $validated['assign_notes'] ?? null,
            'returned_at' => null,
            'returned_by' => null,
            'return_notes' => null,
        ];

        if ($existing) {
            $existing->update($payload);
        } else {
            InspectionToolAssignment::create(array_merge($payload, [
                'inspection_id' => $inspection->id,
                'finding_count' => 0,
            ]));
        }

        $totalDeployed = InspectionToolAssignment::query()
            ->where('tool_setting_id', $toolSetting->id)
            ->whereNull('returned_at')
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $newStatus = $totalDeployed >= (int) $toolSetting->quantity ? 'non_available' : 'available';
        $toolSetting->update(['availability_status' => $newStatus]);

        return redirect()->route('tool-assignments.index')
            ->with('success', "Manually assigned {$newQty} unit(s) of '{$toolSetting->tool_name}' successfully.");
    }

    /**
     * Admin/PM sets the quantity to deploy for a specific assignment.
     */
    public function assignQuantity(Request $request, InspectionToolAssignment $assignment)
    {
        $user = Auth::user();
        if (! $user->hasRole(['Super Admin', 'Store Manager'])) {
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

        // Refresh the page to show updated stock immediately
        return redirect()->route('tool-assignments.index')
            ->with('success', "'{$assignment->tool_name}' marked as returned successfully. Stock updated.");
    }
}
