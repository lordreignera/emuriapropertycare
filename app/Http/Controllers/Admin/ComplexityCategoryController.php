<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplexityCategory;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class ComplexityCategoryController extends Controller
{
    public function index()
    {
        $categories = ComplexityCategory::orderBy('category_name')->get();
        return view('admin.pricing-system.complexity-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.pricing-system.complexity-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'nullable|string|max:50|unique:complexity_categories,category_code',
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['category_code'])) {
            $lastCode = ComplexityCategory::where('category_code', 'like', 'COMPLEX_%')
                ->orderBy('category_code', 'desc')
                ->first();
            
            $nextNumber = 1;
            if ($lastCode) {
                preg_match('/COMPLEX_(\d+)/', $lastCode->category_code, $matches);
                $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            }
            $validated['category_code'] = 'COMPLEX_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        ComplexityCategory::create($validated);

        return redirect()->route('admin.complexity-categories.index')
            ->with('success', 'Complexity category created successfully.');
    }

    public function edit(ComplexityCategory $complexityCategory)
    {
        return view('admin.pricing-system.complexity-categories.edit', compact('complexityCategory'));
    }

    public function update(Request $request, ComplexityCategory $complexityCategory)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|max:50|unique:complexity_categories,category_code,' . $complexityCategory->id,
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $complexityCategory->update($validated);

        return redirect()->route('admin.complexity-categories.index')
            ->with('success', 'Complexity category updated successfully.');
    }

    public function destroy(ComplexityCategory $complexityCategory)
    {
        $complexityCategory->delete();

        return redirect()->route('admin.complexity-categories.index')
            ->with('success', 'Complexity category deleted successfully.');
    }
}
