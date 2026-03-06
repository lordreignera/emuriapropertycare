<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FmcMaterialSetting;
use Illuminate\Http\Request;

class FmcMaterialSettingController extends Controller
{
    public function index()
    {
        $materials = FmcMaterialSetting::query()->orderBy('sort_order')->orderBy('material_name')->get();
        return view('admin.pricing-system.fmc-material-settings.index', compact('materials'));
    }

    public function create()
    {
        return view('admin.pricing-system.fmc-material-settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:150',
            'default_unit' => 'required|string|max:30',
            'default_unit_cost' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        FmcMaterialSetting::create($validated);

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material setting created successfully.');
    }

    public function edit(FmcMaterialSetting $fmcMaterialSetting)
    {
        return view('admin.pricing-system.fmc-material-settings.edit', compact('fmcMaterialSetting'));
    }

    public function update(Request $request, FmcMaterialSetting $fmcMaterialSetting)
    {
        $validated = $request->validate([
            'material_name' => 'required|string|max:150',
            'default_unit' => 'required|string|max:30',
            'default_unit_cost' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

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
            FmcMaterialSetting::updateOrCreate(
                ['material_name' => $row['material_name']],
                [
                    'default_unit' => $row['default_unit'],
                    'default_unit_cost' => $row['default_unit_cost'],
                    'sort_order' => $row['sort_order'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('admin.fmc-material-settings.index')
            ->with('success', 'FMC material settings reloaded to defaults.');
    }
}
