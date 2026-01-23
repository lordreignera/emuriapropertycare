<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class CpiDomainController extends Controller
{
    public function index()
    {
        $domains = CpiDomain::orderBy('domain_number')->get();
        return view('admin.pricing-system.cpi-domains.index', compact('domains'));
    }

    public function create()
    {
        return view('admin.pricing-system.cpi-domains.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cpi_domains,slug',
            'description' => 'nullable|string',
            'max_points' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        CpiDomain::create($validated);

        return redirect()->route('admin.cpi-domains.index')
            ->with('success', 'CPI domain created successfully.');
    }

    public function edit(CpiDomain $cpiDomain)
    {
        return view('admin.pricing-system.cpi-domains.edit', compact('cpiDomain'));
    }

    public function update(Request $request, CpiDomain $cpiDomain)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cpi_domains,slug,' . $cpiDomain->id,
            'description' => 'nullable|string',
            'max_points' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $cpiDomain->update($validated);

        return redirect()->route('admin.cpi-domains.index')
            ->with('success', 'CPI domain updated successfully.');
    }

    public function destroy(CpiDomain $cpiDomain)
    {
        $cpiDomain->delete();

        return redirect()->route('admin.cpi-domains.index')
            ->with('success', 'CPI domain deleted successfully.');
    }
}
