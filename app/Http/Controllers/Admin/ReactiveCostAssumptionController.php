<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReactiveCostAssumption;
use Illuminate\Http\Request;

class ReactiveCostAssumptionController extends Controller
{
    public function index()
    {
        $assumptions = ReactiveCostAssumption::orderBy('sort_order')->get();
        return view('admin.pricing-system.reactive-costs.index', compact('assumptions'));
    }

    public function create()
    {
        return view('admin.pricing-system.reactive-costs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'severity_level' => 'required|string|max:20|unique:reactive_cost_assumptions,severity_level',
            'typical_cost' => 'required|numeric|min:0',
            'annual_probability' => 'required|numeric|min:0|max:1',
            'claimable_fraction' => 'required|numeric|min:0|max:1',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        ReactiveCostAssumption::create($validated);

        return redirect()->route('admin.reactive-costs.index')
            ->with('success', 'Reactive cost assumption created successfully.');
    }

    public function edit(ReactiveCostAssumption $reactiveCost)
    {
        return view('admin.pricing-system.reactive-costs.edit', compact('reactiveCost'));
    }

    public function update(Request $request, ReactiveCostAssumption $reactiveCost)
    {
        $validated = $request->validate([
            'severity_level' => 'required|string|max:20|unique:reactive_cost_assumptions,severity_level,' . $reactiveCost->id,
            'typical_cost' => 'required|numeric|min:0',
            'annual_probability' => 'required|numeric|min:0|max:1',
            'claimable_fraction' => 'required|numeric|min:0|max:1',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $reactiveCost->update($validated);

        return redirect()->route('admin.reactive-costs.index')
            ->with('success', 'Reactive cost assumption updated successfully.');
    }

    public function destroy(ReactiveCostAssumption $reactiveCost)
    {
        $reactiveCost->delete();

        return redirect()->route('admin.reactive-costs.index')
            ->with('success', 'Reactive cost assumption deleted successfully.');
    }
}
