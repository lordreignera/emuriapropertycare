<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuildingComponent;
use App\Models\FindingTemplateSetting;
use App\Models\BuildingSubsystem;
use App\Models\BuildingSystem;
use App\Models\RecommendationSetting;
use App\Support\BuildingTaxonomyResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = RecommendationSetting::query()->with(['system:id,name', 'subsystem:id,name', 'component:id,name']);

        $systemId = $request->integer('building_system_id') ?: null;
        $subsystemId = $request->integer('building_subsystem_id') ?: null;
        $componentId = $request->integer('building_component_id') ?: null;
        $status = $request->input('status', '');
        $search = trim((string) $request->input('search', ''));

        if ($systemId) {
            $query->where('building_system_id', $systemId);
        }

        if ($subsystemId) {
            $query->where('building_subsystem_id', $subsystemId);
        }

        if ($componentId) {
            $query->where('building_component_id', $componentId);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where('recommendation', 'like', '%' . $search . '%');
        }

        $recommendations = $query
            ->orderBy('sort_order')
            ->orderBy('recommendation')
            ->paginate(30)
            ->withQueryString();

        $subsystems = $systemId
            ? BuildingSubsystem::query()->where('building_system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        $components = $subsystemId
            ? BuildingComponent::query()->where('building_subsystem_id', $subsystemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.recommendation-settings.index', compact(
            'recommendations',
            'systems',
            'subsystems',
            'components',
            'systemId',
            'subsystemId',
            'componentId',
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

        return view('admin.pricing-system.recommendation-settings.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recommendation' => 'required|string|max:500',
            'building_system_id' => 'nullable|exists:building_systems,id',
            'building_subsystem_id' => 'nullable|exists:building_subsystems,id',
            'building_component_id' => 'nullable|exists:building_components,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['recommendation'] = trim((string) $validated['recommendation']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['building_subsystem_id'] = $validated['building_subsystem_id'] ?? null;
        $validated['building_component_id'] = $validated['building_component_id'] ?? null;

        if (!empty($validated['building_subsystem_id'])) {
            $subsystem = BuildingSubsystem::query()->find($validated['building_subsystem_id']);
            if ($subsystem && ((int) $subsystem->building_system_id !== (int) ($validated['building_system_id'] ?? 0))) {
                return back()->withErrors([
                    'building_subsystem_id' => 'Selected subsystem does not belong to the selected system.',
                ])->withInput();
            }
        }

        if (!empty($validated['building_component_id'])) {
            $component = BuildingComponent::query()->find($validated['building_component_id']);
            if (!$validated['building_subsystem_id'] || ($component && (int) $component->building_subsystem_id !== (int) $validated['building_subsystem_id'])) {
                return back()->withErrors([
                    'building_component_id' => 'Selected component does not belong to the selected subsystem.',
                ])->withInput();
            }
        }

        RecommendationSetting::create($validated);

        return redirect()->route('admin.recommendation-settings.index')
            ->with('success', 'Recommendation created successfully.');
    }
    public function edit(RecommendationSetting $recommendationSetting)
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

        return view('admin.pricing-system.recommendation-settings.edit', compact('recommendationSetting', 'systems'));
    }

    public function update(Request $request, RecommendationSetting $recommendationSetting)
    {
        $validated = $request->validate([
            'recommendation' => 'required|string|max:500',
            'building_system_id' => 'nullable|exists:building_systems,id',
            'building_subsystem_id' => 'nullable|exists:building_subsystems,id',
            'building_component_id' => 'nullable|exists:building_components,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['recommendation'] = trim((string) $validated['recommendation']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['building_subsystem_id'] = $validated['building_subsystem_id'] ?? null;
        $validated['building_component_id'] = $validated['building_component_id'] ?? null;

        if (!empty($validated['building_subsystem_id'])) {
            $subsystem = BuildingSubsystem::query()->find($validated['building_subsystem_id']);
            if ($subsystem && ((int) $subsystem->building_system_id !== (int) ($validated['building_system_id'] ?? 0))) {
                return back()->withErrors([
                    'building_subsystem_id' => 'Selected subsystem does not belong to the selected system.',
                ])->withInput();
            }
        }

        if (!empty($validated['building_component_id'])) {
            $component = BuildingComponent::query()->find($validated['building_component_id']);
            if (!$validated['building_subsystem_id'] || ($component && (int) $component->building_subsystem_id !== (int) $validated['building_subsystem_id'])) {
                return back()->withErrors([
                    'building_component_id' => 'Selected component does not belong to the selected subsystem.',
                ])->withInput();
            }
        }

        $recommendationSetting->update($validated);

        return redirect()->route('admin.recommendation-settings.index')
            ->with('success', 'Recommendation updated successfully.');
    }
    public function destroy(RecommendationSetting $recommendationSetting)
    {
        $recommendationSetting->delete();

        return redirect()->route('admin.recommendation-settings.index')
            ->with('success', 'Recommendation deleted successfully.');
    }

    public function reloadDefaults()
    {
        $systems = BuildingSystem::query()->get(['id', 'name', 'code', 'metadata']);
        $subsystems = BuildingSubsystem::query()->get(['id', 'building_system_id', 'name', 'metadata']);

        $entries = [];

        foreach ($systems as $system) {
            foreach ((array) ($system->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'building_system_id' => $system->id,
                    'building_subsystem_id' => null,
                    'building_component_id' => null,
                ];
            }
        }

        foreach ($subsystems as $subsystem) {
            foreach ((array) ($subsystem->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'building_system_id' => $subsystem->building_system_id,
                    'building_subsystem_id' => $subsystem->id,
                    'building_component_id' => null,
                ];
            }
        }

        foreach (FindingTemplateSetting::defaults() as $row) {
            $taxonomy = BuildingTaxonomyResolver::resolve($row['system_name'] ?? null, $row['subsystem_name'] ?? null);

            foreach ((array) ($row['default_recommendations'] ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'building_system_id' => $taxonomy['building_system_id'],
                    'building_subsystem_id' => $taxonomy['building_subsystem_id'],
                    'building_component_id' => $taxonomy['building_component_id'],
                ];
            }
        }

        $uniqueEntries = [];
        foreach ($entries as $entry) {
            $recommendationText = trim((string) ($entry['recommendation'] ?? ''));
            if ($recommendationText === '') {
                continue;
            }

            $systemId = $entry['building_system_id'] !== null ? (int) $entry['building_system_id'] : null;
            $subsystemId = $entry['building_subsystem_id'] !== null ? (int) $entry['building_subsystem_id'] : null;
            $componentId = $entry['building_component_id'] !== null ? (int) $entry['building_component_id'] : null;
            $key = ($systemId ?? 'g') . '|' . ($subsystemId ?? 'g') . '|' . ($componentId ?? 'g') . '|' . strtolower($recommendationText);

            $uniqueEntries[$key] = [
                'recommendation' => $recommendationText,
                'building_system_id' => $systemId,
                'building_subsystem_id' => $subsystemId,
                'building_component_id' => $componentId,
            ];
        }

        DB::transaction(function () use ($uniqueEntries) {
            RecommendationSetting::query()->update(['is_active' => false]);

            $sortOrder = 0;
            foreach (array_values($uniqueEntries) as $entry) {
                RecommendationSetting::updateOrCreate(
                    [
                        'recommendation' => $entry['recommendation'],
                        'building_system_id' => $entry['building_system_id'],
                        'building_subsystem_id' => $entry['building_subsystem_id'],
                        'building_component_id' => $entry['building_component_id'],
                    ],
                    [
                        'sort_order' => $sortOrder++,
                        'is_active' => true,
                    ]
                );
            }
        });

        return redirect()->route('admin.recommendation-settings.index')
            ->with('success', 'Recommendation settings reloaded to defaults.');
    }
}
