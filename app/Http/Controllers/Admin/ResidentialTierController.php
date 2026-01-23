<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResidentialSizeTier;
use Illuminate\Http\Request;

class ResidentialTierController extends Controller
{
    public function index()
    {
        $tiers = ResidentialSizeTier::orderBy('min_units')->get();
        return view('admin.pricing-system.residential-tiers.index', compact('tiers'));
    }

    public function create()
    {
        return view('admin.pricing-system.residential-tiers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tier_name' => 'required|string|max:255',
            'min_units' => 'required|integer|min:0',
            'max_units' => 'nullable|integer|min:0',
            'size_factor' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ResidentialSizeTier::create($validated);

        return redirect()->route('admin.residential-tiers.index')
            ->with('success', 'Residential size tier created successfully.');
    }

    public function edit(ResidentialSizeTier $residentialTier)
    {
        return view('admin.pricing-system.residential-tiers.edit', compact('residentialTier'));
    }

    public function update(Request $request, ResidentialSizeTier $residentialTier)
    {
        $validated = $request->validate([
            'tier_name' => 'required|string|max:255',
            'min_units' => 'required|integer|min:0',
            'max_units' => 'nullable|integer|min:0',
            'size_factor' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $residentialTier->update($validated);

        return redirect()->route('admin.residential-tiers.index')
            ->with('success', 'Residential size tier updated successfully.');
    }

    public function destroy(ResidentialSizeTier $residentialTier)
    {
        $residentialTier->delete();

        return redirect()->route('admin.residential-tiers.index')
            ->with('success', 'Residential size tier deleted successfully.');
    }
}
