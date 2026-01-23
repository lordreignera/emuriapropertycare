<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoofAccessCategory;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class RoofAccessController extends Controller
{
    public function index()
    {
        $categories = RoofAccessCategory::orderBy('category_name')->get();
        return view('admin.pricing-system.roof-access.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.pricing-system.roof-access.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'nullable|string|max:50|unique:roof_access_categories,category_code',
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['category_code'])) {
            $lastCode = RoofAccessCategory::where('category_code', 'like', 'ROOF_%')
                ->orderBy('category_code', 'desc')
                ->first();
            
            $nextNumber = 1;
            if ($lastCode) {
                preg_match('/ROOF_(\d+)/', $lastCode->category_code, $matches);
                $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            }
            $validated['category_code'] = 'ROOF_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        RoofAccessCategory::create($validated);

        return redirect()->route('admin.roof-access.index')
            ->with('success', 'Roof access category created successfully.');
    }

    public function edit(RoofAccessCategory $roofAccess)
    {
        return view('admin.pricing-system.roof-access.edit', compact('roofAccess'));
    }

    public function update(Request $request, RoofAccessCategory $roofAccess)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|max:50|unique:roof_access_categories,category_code,' . $roofAccess->id,
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $roofAccess->update($validated);

        return redirect()->route('admin.roof-access.index')
            ->with('success', 'Roof access category updated successfully.');
    }

    public function destroy(RoofAccessCategory $roofAccess)
    {
        $roofAccess->delete();

        return redirect()->route('admin.roof-access.index')
            ->with('success', 'Roof access category deleted successfully.');
    }
}
