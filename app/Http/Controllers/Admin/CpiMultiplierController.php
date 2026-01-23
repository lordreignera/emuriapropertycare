<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpiMultiplier;
use App\Models\CpiBandRange;
use Illuminate\Http\Request;

class CpiMultiplierController extends Controller
{
    public function index()
    {
        $multipliers = CpiMultiplier::orderBy('band_code')->orderBy('multiplier')->get();
        return view('admin.pricing-system.cpi-multipliers.index', compact('multipliers'));
    }

    public function create()
    {
        $cpiBands = CpiBandRange::active()->orderBy('min_score')->get();
        return view('admin.pricing-system.cpi-multipliers.create', compact('cpiBands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cpi_band_range_id' => 'required|exists:cpi_band_ranges,id',
            'multiplier_value' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        CpiMultiplier::create($validated);

        return redirect()->route('admin.cpi-multipliers.index')
            ->with('success', 'CPI multiplier created successfully.');
    }

    public function edit(CpiMultiplier $cpiMultiplier)
    {
        $cpiBands = CpiBandRange::active()->orderBy('min_score')->get();
        return view('admin.pricing-system.cpi-multipliers.edit', compact('cpiMultiplier', 'cpiBands'));
    }

    public function update(Request $request, CpiMultiplier $cpiMultiplier)
    {
        $validated = $request->validate([
            'cpi_band_range_id' => 'required|exists:cpi_band_ranges,id',
            'multiplier_value' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $cpiMultiplier->update($validated);

        return redirect()->route('admin.cpi-multipliers.index')
            ->with('success', 'CPI multiplier updated successfully.');
    }

    public function destroy(CpiMultiplier $cpiMultiplier)
    {
        $cpiMultiplier->delete();

        return redirect()->route('admin.cpi-multipliers.index')
            ->with('success', 'CPI multiplier deleted successfully.');
    }
}
