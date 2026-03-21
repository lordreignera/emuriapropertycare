<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FindingTemplateSetting;
use App\Models\InspectionSystem;
use App\Models\InspectionSubsystem;
use Illuminate\Http\Request;

class FindingTemplateSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = FindingTemplateSetting::query()
            ->with(['system:id,name', 'subsystem:id,name']);

        $systemId  = $request->integer('system_id') ?: null;
        $subsystemId = $request->integer('subsystem_id') ?: null;
        $category  = trim((string) $request->input('category', ''));
        $status    = $request->input('status', '');
        $search    = trim((string) $request->input('search', ''));

        if ($systemId) {
            $query->where('system_id', $systemId);
        }
        if ($subsystemId) {
            $query->where('subsystem_id', $subsystemId);
        }
        if ($category !== '') {
            $query->where('category', $category);
        }
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        if ($search !== '') {
            $query->where('task_question', 'like', '%' . $search . '%');
        }

        $findings = $query
            ->orderBy('sort_order')
            ->orderBy('task_question')
            ->paginate(30)
            ->withQueryString();

        $categories = FindingTemplateSetting::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $subsystems = $systemId
            ? InspectionSubsystem::query()->where('system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.finding-template-settings.index', compact(
            'findings', 'systems', 'subsystems', 'categories', 'systemId', 'subsystemId', 'category', 'status', 'search'
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

        return view('admin.pricing-system.finding-template-settings.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_question'           => 'required|string|max:255',
            'system_id'               => 'nullable|exists:systems,id',
            'subsystem_id'            => 'nullable|exists:subsystems,id',
            'category'                => 'nullable|string|max:120',
            'default_included'        => 'nullable|boolean',
            'default_notes'           => 'nullable|string',
            'default_recommendations'   => 'nullable|array',
            'default_recommendations.*' => 'nullable|string|max:500',
            'sort_order'              => 'nullable|integer|min:0',
            'is_active'               => 'nullable|boolean',
        ]);

        $validated['default_included']      = $request->boolean('default_included', true);
        $validated['is_active']             = $request->boolean('is_active', true);
        $validated['sort_order']            = $validated['sort_order'] ?? 0;
        $validated['subsystem_id']          = $validated['subsystem_id'] ?? null;
        $validated['default_recommendations'] = collect($request->input('default_recommendations', []))
            ->map(fn($r) => trim((string) $r))->filter()->values()->all();

        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                return back()->withErrors(['subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        FindingTemplateSetting::create($validated);

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting created successfully.');
    }

    public function edit(FindingTemplateSetting $findingTemplateSetting)
    {
        $systems = InspectionSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pricing-system.finding-template-settings.edit', compact('findingTemplateSetting', 'systems'));
    }

    public function update(Request $request, FindingTemplateSetting $findingTemplateSetting)
    {
        $validated = $request->validate([
            'task_question'             => 'required|string|max:255',
            'system_id'                 => 'nullable|exists:systems,id',
            'subsystem_id'              => 'nullable|exists:subsystems,id',
            'category'                  => 'nullable|string|max:120',
            'default_included'          => 'nullable|boolean',
            'default_notes'             => 'nullable|string',
            'default_recommendations'   => 'nullable|array',
            'default_recommendations.*' => 'nullable|string|max:500',
            'sort_order'                => 'nullable|integer|min:0',
            'is_active'                 => 'nullable|boolean',
        ]);

        $validated['default_included']      = $request->boolean('default_included');
        $validated['is_active']             = $request->boolean('is_active');
        $validated['sort_order']            = $validated['sort_order'] ?? 0;
        $validated['subsystem_id']          = $validated['subsystem_id'] ?? null;
        $validated['default_recommendations'] = collect($request->input('default_recommendations', []))
            ->map(fn($r) => trim((string) $r))->filter()->values()->all();

        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                return back()->withErrors(['subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        $findingTemplateSetting->update($validated);

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting updated successfully.');
    }

    public function destroy(FindingTemplateSetting $findingTemplateSetting)
    {
        $findingTemplateSetting->delete();

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting deleted successfully.');
    }

    public function reloadDefaults()
    {
        $systemMap = InspectionSystem::query()->pluck('id', 'name');
        $subsystemMap = InspectionSubsystem::query()->get()->keyBy(function ($subsystem) {
            return $subsystem->system_id . '|' . $subsystem->name;
        });
        $activeReferences = [];

        foreach (FindingTemplateSetting::defaults() as $row) {
            $systemId = $systemMap[$row['system_name']] ?? null;
            $subsystemId = null;

            if ($systemId !== null) {
                $subsystemKey = $systemId . '|' . $row['subsystem_name'];
                $subsystemId = optional($subsystemMap->get($subsystemKey))->id;
            }

            $activeReferences[] = $row['task_question'];

            FindingTemplateSetting::updateOrCreate(
                ['task_question' => $row['task_question']],
                [
                    'system_id'               => $systemId,
                    'subsystem_id'            => $subsystemId,
                    'category'                => $row['category'],
                    'default_included'        => $row['default_included'],
                    'default_notes'           => $row['default_notes'],
                    'default_recommendations' => $row['default_recommendations'] ?? [],
                    'sort_order'              => $row['sort_order'],
                    'is_active'               => true,
                ]
            );
        }

        FindingTemplateSetting::query()
            ->whereNotIn('task_question', $activeReferences)
            ->update(['is_active' => false]);

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template settings reloaded to defaults.');
    }
}
