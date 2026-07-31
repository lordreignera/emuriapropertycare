<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingComponent;
use App\Models\FindingTemplateSetting;
use App\Models\BuildingSystem;
use App\Models\BuildingSubsystem;
use App\Support\BuildingTaxonomyResolver;
use Illuminate\Http\Request;

class FindingTemplateSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = FindingTemplateSetting::query()
            ->with(['system:id,name', 'subsystem:id,name', 'component:id,name']);

        $systemId  = $request->integer('building_system_id') ?: null;
        $subsystemId = $request->integer('building_subsystem_id') ?: null;
        $componentId = $request->integer('building_component_id') ?: null;
        $category  = trim((string) $request->input('category', ''));
        $status    = $request->input('status', '');
        $search    = trim((string) $request->input('search', ''));

        if ($systemId) {
            $query->where('building_system_id', $systemId);
        }
        if ($subsystemId) {
            $query->where('building_subsystem_id', $subsystemId);
        }
        if ($componentId) {
            $query->where('building_component_id', $componentId);
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
            ? BuildingSubsystem::query()->where('building_system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        $components = $subsystemId
            ? BuildingComponent::query()->where('building_subsystem_id', $subsystemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.finding-template-settings.index', compact(
            'findings',
            'systems',
            'subsystems',
            'components',
            'categories',
            'systemId',
            'subsystemId',
            'componentId',
            'category',
            'status',
            'search'
        ));
    }

    public function create()
    {
        $systems = BuildingSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }, 'subsystems.components' => function ($query) {
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
            'building_system_id'               => 'nullable|exists:building_systems,id',
            'building_subsystem_id'            => 'nullable|exists:building_subsystems,id',
            'building_component_id'             => 'nullable|exists:building_components,id',
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
        $validated['building_subsystem_id']          = $validated['building_subsystem_id'] ?? null;
        $validated['building_component_id']          = $validated['building_component_id'] ?? null;
        $validated['default_recommendations'] = collect($request->input('default_recommendations', []))
            ->map(fn($r) => trim((string) $r))->filter()->values()->all();

        if (!empty($validated['building_subsystem_id'])) {
            $subsystem = BuildingSubsystem::query()->find($validated['building_subsystem_id']);
            if ($subsystem && ((int) $subsystem->building_system_id !== (int) ($validated['building_system_id'] ?? 0))) {
                return back()->withErrors(['building_subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        if (!empty($validated['building_component_id'])) {
            $component = BuildingComponent::query()->find($validated['building_component_id']);
            if (!$validated['building_subsystem_id'] || ($component && (int) $component->building_subsystem_id !== (int) $validated['building_subsystem_id'])) {
                return back()->withErrors(['building_component_id' => 'Selected component does not belong to the selected subsystem.'])->withInput();
            }
        }

        FindingTemplateSetting::create($validated);

        return redirect()->route('admin.finding-template-settings.index')
            ->with('success', 'Finding template setting created successfully.');
    }

    public function edit(FindingTemplateSetting $findingTemplateSetting)
    {
        $systems = BuildingSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }, 'subsystems.components' => function ($query) {
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
            'building_system_id'                 => 'nullable|exists:building_systems,id',
            'building_subsystem_id'              => 'nullable|exists:building_subsystems,id',
            'building_component_id'               => 'nullable|exists:building_components,id',
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
        $validated['building_subsystem_id']          = $validated['building_subsystem_id'] ?? null;
        $validated['building_component_id']          = $validated['building_component_id'] ?? null;
        $validated['default_recommendations'] = collect($request->input('default_recommendations', []))
            ->map(fn($r) => trim((string) $r))->filter()->values()->all();

        if (!empty($validated['building_subsystem_id'])) {
            $subsystem = BuildingSubsystem::query()->find($validated['building_subsystem_id']);
            if ($subsystem && ((int) $subsystem->building_system_id !== (int) ($validated['building_system_id'] ?? 0))) {
                return back()->withErrors(['building_subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        if (!empty($validated['building_component_id'])) {
            $component = BuildingComponent::query()->find($validated['building_component_id']);
            if (!$validated['building_subsystem_id'] || ($component && (int) $component->building_subsystem_id !== (int) $validated['building_subsystem_id'])) {
                return back()->withErrors(['building_component_id' => 'Selected component does not belong to the selected subsystem.'])->withInput();
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
        $activeReferences = [];

        foreach (FindingTemplateSetting::defaults() as $row) {
            $taxonomy = BuildingTaxonomyResolver::resolve($row['system_name'] ?? null, $row['subsystem_name'] ?? null);

            $activeReferences[] = $row['task_question'];

            FindingTemplateSetting::updateOrCreate(
                ['task_question' => $row['task_question']],
                [
                    'building_system_id' => $taxonomy['building_system_id'],
                    'building_subsystem_id' => $taxonomy['building_subsystem_id'],
                    'building_component_id' => $taxonomy['building_component_id'],
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
