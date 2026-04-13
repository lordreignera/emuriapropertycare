<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FindingTemplateSetting;
use App\Models\InspectionSubsystem;
use App\Models\InspectionSystem;
use App\Models\RecommendationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = RecommendationSetting::query()->with(['system:id,name', 'subsystem:id,name']);

        $systemId = $request->integer('system_id') ?: null;
        $subsystemId = $request->integer('subsystem_id') ?: null;
        $status = $request->input('status', '');
        $search = trim((string) $request->input('search', ''));

        if ($systemId) {
            $query->where('system_id', $systemId);
        }

        if ($subsystemId) {
            $query->where('subsystem_id', $subsystemId);
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
            ? InspectionSubsystem::query()->where('system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.recommendation-settings.index', compact(
            'recommendations', 'systems', 'subsystems', 'systemId', 'subsystemId', 'status', 'search'
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

        return view('admin.pricing-system.recommendation-settings.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recommendation' => 'required|string|max:500',
            'system_id' => 'nullable|exists:systems,id',
            'subsystem_id' => 'nullable|exists:subsystems,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['recommendation'] = trim((string) $validated['recommendation']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['subsystem_id'] = $validated['subsystem_id'] ?? null;

        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                return back()->withErrors([
                    'subsystem_id' => 'Selected subsystem does not belong to the selected system.',
                ])->withInput();
            }
        }

        RecommendationSetting::create($validated);

        return redirect()->route('admin.recommendation-settings.index')
            ->with('success', 'Recommendation created successfully.');
    }

    public function edit(RecommendationSetting $recommendationSetting)
    {
        $systems = InspectionSystem::query()
            ->with(['subsystems' => function ($query) {
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
            'system_id' => 'nullable|exists:systems,id',
            'subsystem_id' => 'nullable|exists:subsystems,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['recommendation'] = trim((string) $validated['recommendation']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['subsystem_id'] = $validated['subsystem_id'] ?? null;

        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                return back()->withErrors([
                    'subsystem_id' => 'Selected subsystem does not belong to the selected system.',
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
        $systems = InspectionSystem::query()->get(['id', 'name', 'recommended_actions']);
        $subsystems = InspectionSubsystem::query()->get(['id', 'system_id', 'name', 'recommended_actions']);

        $systemNameToId = $systems->pluck('id', 'name');
        $subsystemMap = $subsystems->keyBy(function ($subsystem) {
            return $subsystem->system_id . '|' . $subsystem->name;
        });

        $entries = [];

        foreach ($systems as $system) {
            foreach ((array) ($system->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'system_id' => $system->id,
                    'subsystem_id' => null,
                ];
            }
        }

        foreach ($subsystems as $subsystem) {
            foreach ((array) ($subsystem->recommended_actions ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'system_id' => $subsystem->system_id,
                    'subsystem_id' => $subsystem->id,
                ];
            }
        }

        foreach (FindingTemplateSetting::defaults() as $row) {
            $systemId = $systemNameToId[$row['system_name']] ?? null;
            $subsystemId = null;

            if ($systemId !== null) {
                $subsystemKey = $systemId . '|' . $row['subsystem_name'];
                $subsystemId = optional($subsystemMap->get($subsystemKey))->id;
            }

            foreach ((array) ($row['default_recommendations'] ?? []) as $recommendation) {
                $entries[] = [
                    'recommendation' => $recommendation,
                    'system_id' => $systemId,
                    'subsystem_id' => $subsystemId,
                ];
            }
        }

        $uniqueEntries = [];
        foreach ($entries as $entry) {
            $recommendationText = trim((string) ($entry['recommendation'] ?? ''));
            if ($recommendationText === '') {
                continue;
            }

            $systemId = $entry['system_id'] !== null ? (int) $entry['system_id'] : null;
            $subsystemId = $entry['subsystem_id'] !== null ? (int) $entry['subsystem_id'] : null;
            $key = ($systemId ?? 'g') . '|' . ($subsystemId ?? 'g') . '|' . strtolower($recommendationText);

            $uniqueEntries[$key] = [
                'recommendation' => $recommendationText,
                'system_id' => $systemId,
                'subsystem_id' => $subsystemId,
            ];
        }

        DB::transaction(function () use ($uniqueEntries) {
            RecommendationSetting::query()->update(['is_active' => false]);

            $sortOrder = 0;
            foreach (array_values($uniqueEntries) as $entry) {
                RecommendationSetting::updateOrCreate(
                    [
                        'recommendation' => $entry['recommendation'],
                        'system_id' => $entry['system_id'],
                        'subsystem_id' => $entry['subsystem_id'],
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
