<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpiBandRange;
use Illuminate\Http\Request;

class CpiBandController extends Controller
{
    public function index()
    {
        $cpiBands = CpiBandRange::orderBy('min_score')->get();
        return view('admin.pricing-system.cpi-bands.index', compact('cpiBands'));
    }

    public function create()
    {
        return view('admin.pricing-system.cpi-bands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'band_name' => 'required|string|max:255',
            'band_slug' => 'required|string|max:255|unique:cpi_band_ranges,band_slug',
            'min_score' => 'required|integer|min:0',
            'max_score' => 'required|integer|min:0',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        CpiBandRange::create($validated);

        return redirect()->route('admin.cpi-bands.index')
            ->with('success', 'CPI band range created successfully.');
    }

    public function edit(CpiBandRange $cpiBand)
    {
        return view('admin.pricing-system.cpi-bands.edit', compact('cpiBand'));
    }

    public function update(Request $request, CpiBandRange $cpiBand)
    {
        $validated = $request->validate([
            'band_name' => 'required|string|max:255',
            'band_slug' => 'required|string|max:255|unique:cpi_band_ranges,band_slug,' . $cpiBand->id,
            'min_score' => 'required|integer|min:0',
            'max_score' => 'required|integer|min:0',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $cpiBand->update($validated);

        return redirect()->route('admin.cpi-bands.index')
            ->with('success', 'CPI band range updated successfully.');
    }

    public function destroy(CpiBandRange $cpiBand)
    {
        $cpiBand->delete();

        return redirect()->route('admin.cpi-bands.index')
            ->with('success', 'CPI band range deleted successfully.');
    }
}
