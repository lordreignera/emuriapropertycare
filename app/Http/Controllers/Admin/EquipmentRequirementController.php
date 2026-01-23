<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EquipmentRequirement;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class EquipmentRequirementController extends Controller
{
    public function index()
    {
        $equipment = EquipmentRequirement::orderBy('sort_order')->orderBy('requirement_name')->get();
        return view('admin.pricing-system.equipment-requirements.index', compact('equipment'));
    }

    public function create()
    {
        return view('admin.pricing-system.equipment-requirements.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requirement_code' => 'nullable|string|max:50|unique:equipment_requirements,requirement_code',
            'requirement_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['requirement_code'])) {
            $lastCode = EquipmentRequirement::where('requirement_code', 'like', 'EQUIP_%')
                ->orderBy('requirement_code', 'desc')
                ->first();
            
            $nextNumber = 1;
            if ($lastCode) {
                preg_match('/EQUIP_(\d+)/', $lastCode->requirement_code, $matches);
                $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            }
            $validated['requirement_code'] = 'EQUIP_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        EquipmentRequirement::create($validated);

        return redirect()->route('admin.equipment-requirements.index')
            ->with('success', 'Equipment requirement created successfully.');
    }

    public function edit(EquipmentRequirement $equipmentRequirement)
    {
        return view('admin.pricing-system.equipment-requirements.edit', compact('equipmentRequirement'));
    }

    public function update(Request $request, EquipmentRequirement $equipmentRequirement)
    {
        $validated = $request->validate([
            'requirement_code' => 'required|string|max:50|unique:equipment_requirements,requirement_code,' . $equipmentRequirement->id,
            'requirement_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $equipmentRequirement->update($validated);

        return redirect()->route('admin.equipment-requirements.index')
            ->with('success', 'Equipment requirement updated successfully.');
    }

    public function destroy(EquipmentRequirement $equipmentRequirement)
    {
        $equipmentRequirement->delete();

        return redirect()->route('admin.equipment-requirements.index')
            ->with('success', 'Equipment requirement deleted successfully.');
    }
}
