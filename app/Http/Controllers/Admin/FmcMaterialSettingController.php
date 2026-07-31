<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FmcMaterialSetting;
use App\Models\BuildingSystem;
use App\Models\BuildingSubsystem;
use App\Support\BuildingTaxonomyResolver;
use Illuminate\Http\Request;

class FmcMaterialSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = BuildingSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = FmcMaterialSetting::query()
            ->with(['system:id,name', 'subsystem:id,name']);

        $systemId    = $request->integer('building_system_id') ?: null;
        $subsystemId = $request->integer('building_subsystem_id') ?: null;
        $status      = $request->input('status', '');
        $search      = trim((string) $request->input('search', ''));

        if ($systemId) {
            $query->where('building_system_id', $systemId);
        }
        if ($subsystemId) {
            $query->where('building_subsystem_id', $subsystemId);
        }
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        if ($search !== '') {
            $query->where('material_name', 'like', '%' . $search . '%');
        }

        $materials = $query
            ->orderBy('sort_order')
            ->orderBy('material_name')
            ->paginate(30)
            ->withQueryString();

        $subsystems = $systemId
            ? BuildingSubsystem::query()->where('building_system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.fmc-material-settings.index', compact(
            'materials', 'systems', 'subsystems', 'systemId', 'subsystemId', 'status', 'search'
        ));
    }

    public function create()
    {
        $systems = BuildingSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pricing-system.fmc-material-settings.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_name'    => 'required|string|max:150',
            'default_unit'     => 'required|string|max:30',
            'default_unit_cost' => 'required|numeric|min:0',
            'hst_rate'         => 'nullable|numeric|min:0|max:100',
            'pst_rate'         => 'nullable|numeric|min:0|max:100',
            'description'      => 'nullable|string',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable|boolean',
            'building_system_id'        => 'nullable|exists:building_systems,id',
            'building_subsystem_id'     => 'nullable|exists:building_subsystems,id',
        ]);

        $validated['is_active']    = $request->boolean('is_active', true);
        $validated['sort_order']   = $validated['sort_order'] ?? 0;
        $validated['building_subsystem_id'] = $validated['building_subsystem_id'] ?? null;
        $validated['hst_rate']     = $validated['hst_rate'] ?? 5.00;
        $validated['pst_rate']     = $validated['pst_rate'] ?? 7.00;

        if (!empty($validated['building_subsystem_id'])) {
            $subsystem = BuildingSubsystem::query()->find($validated['building_subsystem_id']);
            if ($subsystem && ((int) $subsystem->building_system_id !== (int) ($validated['building_system_id'] ?? 0))) {
                return back()->withErrors(['building_subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        FmcMaterialSetting::create($validated);

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material setting created successfully.');
    }

    public function edit(FmcMaterialSetting $fmcMaterialSetting)
    {
        $systems = BuildingSystem::query()
            ->with(['subsystems' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.pricing-system.fmc-material-settings.edit', compact('fmcMaterialSetting', 'systems'));
    }

    public function update(Request $request, FmcMaterialSetting $fmcMaterialSetting)
    {
        $validated = $request->validate([
            'material_name'    => 'required|string|max:150',
            'default_unit'     => 'required|string|max:30',
            'default_unit_cost' => 'required|numeric|min:0',
            'hst_rate'         => 'nullable|numeric|min:0|max:100',
            'pst_rate'         => 'nullable|numeric|min:0|max:100',
            'description'      => 'nullable|string',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable|boolean',
            'building_system_id'        => 'nullable|exists:building_systems,id',
            'building_subsystem_id'     => 'nullable|exists:building_subsystems,id',
        ]);

        $validated['is_active']    = $request->boolean('is_active');
        $validated['sort_order']   = $validated['sort_order'] ?? 0;
        $validated['building_subsystem_id'] = $validated['building_subsystem_id'] ?? null;
        $validated['hst_rate']     = $validated['hst_rate'] ?? 5.00;
        $validated['pst_rate']     = $validated['pst_rate'] ?? 7.00;

        if (!empty($validated['building_subsystem_id'])) {
            $subsystem = BuildingSubsystem::query()->find($validated['building_subsystem_id']);
            if ($subsystem && ((int) $subsystem->building_system_id !== (int) ($validated['building_system_id'] ?? 0))) {
                return back()->withErrors(['building_subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        $fmcMaterialSetting->update($validated);

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material setting updated successfully.');
    }

    public function destroy(FmcMaterialSetting $fmcMaterialSetting)
    {
        $fmcMaterialSetting->delete();

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material setting deleted successfully.');
    }

    public function reloadDefaults()
    {
        foreach (FmcMaterialSetting::defaults() as $row) {
            $taxonomy = BuildingTaxonomyResolver::resolve($row['system_name'] ?? null, $row['subsystem_name'] ?? null);

            FmcMaterialSetting::updateOrCreate(
                [
                    'material_name' => $row['material_name'],
                    'building_system_id' => $taxonomy['building_system_id'],
                    'building_subsystem_id' => $taxonomy['building_subsystem_id'],
                    'building_component_id' => $taxonomy['building_component_id'],
                ],
                [
                    'default_unit'     => $row['default_unit'],
                    'default_unit_cost' => $row['default_unit_cost'],
                    'sort_order'       => $row['sort_order'],
                    'description'      => $row['description'] ?? null,
                    'is_active'        => true,
                ]
            );
        }

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material settings reloaded to defaults.');
    }
}

