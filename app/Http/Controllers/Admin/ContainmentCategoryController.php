<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContainmentCategory;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class ContainmentCategoryController extends Controller
{
    public function index()
    {
        $categories = ContainmentCategory::orderBy('category_name')->get();
        return view('admin.pricing-system.containment-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.pricing-system.containment-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'nullable|string|max:50|unique:containment_categories,category_code',
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['category_code'])) {
            $lastCode = ContainmentCategory::where('category_code', 'like', 'CONTAIN_%')
                ->orderBy('category_code', 'desc')
                ->first();
            
            $nextNumber = 1;
            if ($lastCode) {
                preg_match('/CONTAIN_(\d+)/', $lastCode->category_code, $matches);
                $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            }
            $validated['category_code'] = 'CONTAIN_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        ContainmentCategory::create($validated);

        return redirect()->route('admin.containment-categories.index')
            ->with('success', 'Containment category created successfully.');
    }

    public function edit(ContainmentCategory $containmentCategory)
    {
        return view('admin.pricing-system.containment-categories.edit', compact('containmentCategory'));
    }

    public function update(Request $request, ContainmentCategory $containmentCategory)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|max:50|unique:containment_categories,category_code,' . $containmentCategory->id,
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $containmentCategory->update($validated);

        return redirect()->route('admin.containment-categories.index')
            ->with('success', 'Containment category updated successfully.');
    }

    public function destroy(ContainmentCategory $containmentCategory)
    {
        $containmentCategory->delete();

        return redirect()->route('admin.containment-categories.index')
            ->with('success', 'Containment category deleted successfully.');
    }
}
