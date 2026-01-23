<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplyLineMaterial;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class SupplyMaterialController extends Controller
{
    public function index()
    {
        $materials = SupplyLineMaterial::orderBy('material_name')->get();
        return view('admin.pricing-system.supply-materials.index', compact('materials'));
    }

    public function create()
    {
        return view('admin.pricing-system.supply-materials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_code' => 'nullable|string|max:50|unique:supply_line_materials,material_code',
            'material_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'risk_level' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['material_code'])) {
            $lastCode = SupplyLineMaterial::where('material_code', 'like', 'MATERIAL_%')
                ->orderBy('material_code', 'desc')
                ->first();
            
            $nextNumber = 1;
            if ($lastCode) {
                preg_match('/MATERIAL_(\d+)/', $lastCode->material_code, $matches);
                $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            }
            $validated['material_code'] = 'MATERIAL_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        SupplyLineMaterial::create($validated);

        return redirect()->route('admin.supply-materials.index')
            ->with('success', 'Supply line material created successfully.');
    }

    public function edit(SupplyLineMaterial $supplyMaterial)
    {
        return view('admin.pricing-system.supply-materials.edit', compact('supplyMaterial'));
    }

    public function update(Request $request, SupplyLineMaterial $supplyMaterial)
    {
        $validated = $request->validate([
            'material_code' => 'required|string|max:50|unique:supply_line_materials,material_code,' . $supplyMaterial->id,
            'material_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'risk_level' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $supplyMaterial->update($validated);

        return redirect()->route('admin.supply-materials.index')
            ->with('success', 'Supply line material updated successfully.');
    }

    public function destroy(SupplyLineMaterial $supplyMaterial)
    {
        $supplyMaterial->delete();

        return redirect()->route('admin.supply-materials.index')
            ->with('success', 'Supply line material deleted successfully.');
    }
}
