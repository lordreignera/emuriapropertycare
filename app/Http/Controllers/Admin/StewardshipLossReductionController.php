<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StewardshipLossReduction;
use Illuminate\Http\Request;

class StewardshipLossReductionController extends Controller
{
    public function index()
    {
        $reductions = StewardshipLossReduction::orderBy('sort_order')->get();
        return view('admin.pricing-system.stewardship-loss.index', compact('reductions'));
    }

    public function create()
    {
        return view('admin.pricing-system.stewardship-loss.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cpi_band' => 'required|string|max:10|unique:stewardship_loss_reductions,cpi_band',
            'loss_reduction' => 'required|numeric|min:0|max:1',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        StewardshipLossReduction::create($validated);

        return redirect()->route('admin.stewardship-loss.index')
            ->with('success', 'Stewardship loss reduction created successfully.');
    }

    public function edit(StewardshipLossReduction $stewardshipLoss)
    {
        return view('admin.pricing-system.stewardship-loss.edit', compact('stewardshipLoss'));
    }

    public function update(Request $request, StewardshipLossReduction $stewardshipLoss)
    {
        $validated = $request->validate([
            'cpi_band' => 'required|string|max:10|unique:stewardship_loss_reductions,cpi_band,' . $stewardshipLoss->id,
            'loss_reduction' => 'required|numeric|min:0|max:1',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $stewardshipLoss->update($validated);

        return redirect()->route('admin.stewardship-loss.index')
            ->with('success', 'Stewardship loss reduction updated successfully.');
    }

    public function destroy(StewardshipLossReduction $stewardshipLoss)
    {
        $stewardshipLoss->delete();

        return redirect()->route('admin.stewardship-loss.index')
            ->with('success', 'Stewardship loss reduction deleted successfully.');
    }
}
