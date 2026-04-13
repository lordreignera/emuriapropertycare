<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FindingTemplateSetting;
use App\Models\InspectionSubsystem;
use App\Models\InspectionSystem;
use App\Models\ToolSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ToolSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = ToolSetting::query()
            ->with(['system:id,name', 'subsystem:id,name', 'findingTemplateSetting:id,task_question']);

        $systemId = $request->integer('system_id') ?: null;
        $subsystemId = $request->integer('subsystem_id') ?: null;
        $ownership = trim((string) $request->input('ownership_status', ''));
        $availability = trim((string) $request->input('availability_status', ''));
        $status = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        if ($systemId) {
            $query->where('system_id', $systemId);
        }
        if ($subsystemId) {
            $query->where('subsystem_id', $subsystemId);
        }
        if (in_array($ownership, ['owned', 'hired'], true)) {
            $query->where('ownership_status', $ownership);
        }
        if (in_array($availability, ['available', 'non_available'], true)) {
            $query->where('availability_status', $availability);
        }
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tool_name', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        $tools = $query
            ->orderBy('sort_order')
            ->orderBy('tool_name')
            ->paginate(30)
            ->withQueryString();

        $subsystems = $systemId
            ? InspectionSubsystem::query()->where('system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.tool-settings.index', compact(
            'tools', 'systems', 'subsystems', 'systemId', 'subsystemId', 'ownership', 'availability', 'status', 'search'
        ));
    }

    public function create()
    {
        $systems = InspectionSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $findingTemplates = FindingTemplateSetting::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('task_question')
            ->get(['id', 'task_question', 'system_id', 'subsystem_id']);

        return view('admin.pricing-system.tool-settings.create', compact('systems', 'findingTemplates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tool_name' => 'required|string|max:150',
            'system_id' => 'nullable|exists:systems,id',
            'subsystem_id' => 'nullable|exists:subsystems,id',
            'finding_template_setting_id' => 'nullable|exists:finding_template_settings,id',
            'ownership_status' => 'required|in:owned,hired',
            'availability_status' => 'required|in:available,non_available',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['tool_name'] = trim((string) $validated['tool_name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['subsystem_id'] = $validated['subsystem_id'] ?? null;
        $validated['finding_template_setting_id'] = $validated['finding_template_setting_id'] ?? null;

        $this->validateScopeConsistency($validated);

        ToolSetting::create($validated);

        return redirect()->route('admin.tool-settings.index')
            ->with('success', 'Tool setting created successfully.');
    }

    public function edit(ToolSetting $toolSetting)
    {
        $systems = InspectionSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $findingTemplates = FindingTemplateSetting::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('task_question')
            ->get(['id', 'task_question', 'system_id', 'subsystem_id']);

        return view('admin.pricing-system.tool-settings.edit', compact('toolSetting', 'systems', 'findingTemplates'));
    }

    public function update(Request $request, ToolSetting $toolSetting)
    {
        $validated = $request->validate([
            'tool_name' => 'required|string|max:150',
            'system_id' => 'nullable|exists:systems,id',
            'subsystem_id' => 'nullable|exists:subsystems,id',
            'finding_template_setting_id' => 'nullable|exists:finding_template_settings,id',
            'ownership_status' => 'required|in:owned,hired',
            'availability_status' => 'required|in:available,non_available',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['tool_name'] = trim((string) $validated['tool_name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['subsystem_id'] = $validated['subsystem_id'] ?? null;
        $validated['finding_template_setting_id'] = $validated['finding_template_setting_id'] ?? null;

        $this->validateScopeConsistency($validated);

        $toolSetting->update($validated);

        return redirect()->route('admin.tool-settings.index')
            ->with('success', 'Tool setting updated successfully.');
    }

    public function destroy(ToolSetting $toolSetting)
    {
        $toolSetting->delete();

        return redirect()->route('admin.tool-settings.index')
            ->with('success', 'Tool setting deleted successfully.');
    }

    private function validateScopeConsistency(array $validated): void
    {
        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                throw ValidationException::withMessages([
                    'subsystem_id' => 'Selected subsystem does not belong to the selected system.',
                ]);
            }
        }

        if (!empty($validated['finding_template_setting_id'])) {
            $finding = FindingTemplateSetting::query()->find($validated['finding_template_setting_id']);
            if ($finding) {
                $systemMismatch = !empty($validated['system_id']) && (int) $finding->system_id !== (int) $validated['system_id'];
                $subsystemMismatch = !empty($validated['subsystem_id']) && (int) $finding->subsystem_id !== (int) $validated['subsystem_id'];
                if ($systemMismatch || $subsystemMismatch) {
                    throw ValidationException::withMessages([
                        'finding_template_setting_id' => 'Selected finding does not match selected system/subsystem scope.',
                    ]);
                }
            }
        }
    }
}
