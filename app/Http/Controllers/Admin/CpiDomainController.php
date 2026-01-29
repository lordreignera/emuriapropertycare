<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpiDomain;
use App\Models\CpiScoringFactor;
use Illuminate\Http\Request;

class CpiDomainController extends Controller
{
    public function index()
    {
        $domains = CpiDomain::with('activeFactors')
            ->orderBy('domain_number')
            ->get();
        return view('admin.pricing-system.cpi-domains.index', compact('domains'));
    }

    public function create()
    {
        return view('admin.pricing-system.cpi-domains.create');
    }

    public function show(CpiDomain $cpiDomain)
    {
        $cpiDomain->load('activeFactors');
        return view('admin.pricing-system.cpi-domains.show', compact('cpiDomain'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:100',
            'domain_code' => 'required|string|max:50|unique:cpi_domains,domain_code',
            'domain_number' => 'required|integer|unique:cpi_domains,domain_number',
            'description' => 'nullable|string',
            'max_possible_points' => 'required|integer|min:0',
            'calculation_method' => 'required|string|in:sum,max,lookup,formula',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
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
            'domain_name' => 'required|string|max:100',
            'domain_code' => 'required|string|max:50|unique:cpi_domains,domain_code,' . $cpiDomain->id,
            'domain_number' => 'required|integer|unique:cpi_domains,domain_number,' . $cpiDomain->id,
            'description' => 'nullable|string',
            'max_possible_points' => 'required|integer|min:0',
            'calculation_method' => 'required|string|in:sum,max,lookup,formula',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
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

    // Factor Management Methods
    public function createFactor(CpiDomain $cpiDomain)
    {
        return view('admin.pricing-system.cpi-domains.factors.create', compact('cpiDomain'));
    }

    public function storeFactor(Request $request, CpiDomain $cpiDomain)
    {
        $validated = $request->validate([
            'factor_code' => 'required|string|max:50|unique:cpi_scoring_factors,factor_code,NULL,id,domain_id,' . $cpiDomain->id,
            'factor_label' => 'required|string|max:200',
            'field_type' => 'required|string|in:yes_no,lookup,numeric,calculated',
            'lookup_table' => 'nullable|string|max:50',
            'max_points' => 'required|integer|min:0',
            'calculation_rule' => 'nullable|json',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'help_text' => 'nullable|string',
        ]);

        $validated['domain_id'] = $cpiDomain->id;
        CpiScoringFactor::create($validated);

        return redirect()->route('admin.cpi-domains.show', $cpiDomain)
            ->with('success', 'Scoring factor added successfully.');
    }

    public function editFactor(CpiDomain $cpiDomain, CpiScoringFactor $factor)
    {
        return view('admin.pricing-system.cpi-domains.factors.edit', compact('cpiDomain', 'factor'));
    }

    public function updateFactor(Request $request, CpiDomain $cpiDomain, CpiScoringFactor $factor)
    {
        $validated = $request->validate([
            'factor_code' => 'required|string|max:50|unique:cpi_scoring_factors,factor_code,' . $factor->id . ',id,domain_id,' . $cpiDomain->id,
            'factor_label' => 'required|string|max:200',
            'field_type' => 'required|string|in:yes_no,lookup,numeric,calculated',
            'lookup_table' => 'nullable|string|max:50',
            'max_points' => 'required|integer|min:0',
            'calculation_rule' => 'nullable|json',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'help_text' => 'nullable|string',
        ]);

        $factor->update($validated);

        return redirect()->route('admin.cpi-domains.show', $cpiDomain)
            ->with('success', 'Scoring factor updated successfully.');
    }

    public function destroyFactor(CpiDomain $cpiDomain, CpiScoringFactor $factor)
    {
        $factor->delete();

        return redirect()->route('admin.cpi-domains.show', $cpiDomain)
            ->with('success', 'Scoring factor deleted successfully.');
    }
}
