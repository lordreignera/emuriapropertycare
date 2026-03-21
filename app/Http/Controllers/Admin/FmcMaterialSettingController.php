<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FmcMaterialSetting;
use App\Models\InspectionSystem;
use App\Models\InspectionSubsystem;
use Illuminate\Http\Request;

class FmcMaterialSettingController extends Controller
{
    public function index(Request $request)
    {
        $systems = InspectionSystem::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $query = FmcMaterialSetting::query()
            ->with(['system:id,name', 'subsystem:id,name']);

        $systemId    = $request->integer('system_id') ?: null;
        $subsystemId = $request->integer('subsystem_id') ?: null;
        $status      = $request->input('status', '');
        $search      = trim((string) $request->input('search', ''));

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
            $query->where('material_name', 'like', '%' . $search . '%');
        }

        $materials = $query
            ->orderBy('sort_order')
            ->orderBy('material_name')
            ->paginate(30)
            ->withQueryString();

        $subsystems = $systemId
            ? InspectionSubsystem::query()->where('system_id', $systemId)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.pricing-system.fmc-material-settings.index', compact(
            'materials', 'systems', 'subsystems', 'systemId', 'subsystemId', 'status', 'search'
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

        return view('admin.pricing-system.fmc-material-settings.create', compact('systems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_name'    => 'required|string|max:150',
            'default_unit'     => 'required|string|max:30',
            'default_unit_cost' => 'required|numeric|min:0',
            'description'      => 'nullable|string',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable|boolean',
            'system_id'        => 'nullable|exists:systems,id',
            'subsystem_id'     => 'nullable|exists:subsystems,id',
        ]);

        $validated['is_active']    = $request->boolean('is_active', true);
        $validated['sort_order']   = $validated['sort_order'] ?? 0;
        $validated['subsystem_id'] = $validated['subsystem_id'] ?? null;

        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                return back()->withErrors(['subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
            }
        }

        FmcMaterialSetting::create($validated);

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material setting created successfully.');
    }

    public function edit(FmcMaterialSetting $fmcMaterialSetting)
    {
        $systems = InspectionSystem::query()
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
            'description'      => 'nullable|string',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'nullable|boolean',
            'system_id'        => 'nullable|exists:systems,id',
            'subsystem_id'     => 'nullable|exists:subsystems,id',
        ]);

        $validated['is_active']    = $request->boolean('is_active');
        $validated['sort_order']   = $validated['sort_order'] ?? 0;
        $validated['subsystem_id'] = $validated['subsystem_id'] ?? null;

        if (!empty($validated['subsystem_id'])) {
            $subsystem = InspectionSubsystem::query()->find($validated['subsystem_id']);
            if ($subsystem && ((int) $subsystem->system_id !== (int) ($validated['system_id'] ?? 0))) {
                return back()->withErrors(['subsystem_id' => 'Selected subsystem does not belong to the selected system.'])->withInput();
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
        $systemMap = InspectionSystem::query()->pluck('id', 'name');
        $subsystemMap = InspectionSubsystem::query()->get()->keyBy(function ($subsystem) {
            return $subsystem->system_id . '|' . $subsystem->name;
        });

        foreach (FmcMaterialSetting::defaults() as $row) {
            $systemId = $systemMap[$row['system_name']] ?? null;
            $subsystemId = null;

            if ($systemId !== null) {
                $subsystemKey = $systemId . '|' . $row['subsystem_name'];
                $subsystemId = optional($subsystemMap->get($subsystemKey))->id;
            }

            FmcMaterialSetting::updateOrCreate(
                [
                    'material_name' => $row['material_name'],
                    'system_id'     => $systemId,
                    'subsystem_id'  => $subsystemId,
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

