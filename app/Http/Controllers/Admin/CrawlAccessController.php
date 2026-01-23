<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrawlAccessCategory;
use App\Models\CpiDomain;
use Illuminate\Http\Request;

class CrawlAccessController extends Controller
{
    public function index()
    {
        $categories = CrawlAccessCategory::orderBy('category_name')->get();
        return view('admin.pricing-system.crawl-access.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.pricing-system.crawl-access.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'nullable|string|max:50|unique:crawl_access_categories,category_code',
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['category_code'])) {
            $lastCode = CrawlAccessCategory::where('category_code', 'like', 'CRAWL_%')
                ->orderBy('category_code', 'desc')
                ->first();
            
            $nextNumber = 1;
            if ($lastCode) {
                preg_match('/CRAWL_(\d+)/', $lastCode->category_code, $matches);
                $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            }
            $validated['category_code'] = 'CRAWL_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        CrawlAccessCategory::create($validated);

        return redirect()->route('admin.crawl-access.index')
            ->with('success', 'Crawl space access category created successfully.');
    }

    public function edit(CrawlAccessCategory $crawlAccess)
    {
        return view('admin.pricing-system.crawl-access.edit', compact('crawlAccess'));
    }

    public function update(Request $request, CrawlAccessCategory $crawlAccess)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|max:50|unique:crawl_access_categories,category_code,' . $crawlAccess->id,
            'category_name' => 'required|string|max:100',
            'score_points' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $crawlAccess->update($validated);

        return redirect()->route('admin.crawl-access.index')
            ->with('success', 'Crawl space access category updated successfully.');
    }

    public function destroy(CrawlAccessCategory $crawlAccess)
    {
        $crawlAccess->delete();

        return redirect()->route('admin.crawl-access.index')
            ->with('success', 'Crawl space access category deleted successfully.');
    }
}
