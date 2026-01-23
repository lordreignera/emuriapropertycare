<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPackage;
use App\Models\PackagePricing;
use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PricingPackageController extends Controller
{
    public function index()
    {
        $packages = PricingPackage::with(['packagePricing.propertyType'])
            ->orderBy('sort_order')
            ->orderBy('package_name')
            ->get();
        
        $propertyTypes = PropertyType::active()->orderBy('type_name')->get();
        
        return view('admin.pricing-system.pricing-packages.index', compact('packages', 'propertyTypes'));
    }

    public function create()
    {
        $propertyTypes = PropertyType::active()->orderBy('type_name')->get();
        return view('admin.pricing-system.pricing-packages.create', compact('propertyTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'prices' => 'required|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Convert features from string to array
            $features = $validated['features'] 
                ? array_filter(array_map('trim', explode("\n", $validated['features']))) 
                : [];

            // Create the package
            $package = PricingPackage::create([
                'package_name' => $validated['package_name'],
                'description' => $validated['description'],
                'features' => $features,
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            // Create pricing for each property type with a price
            foreach ($validated['prices'] as $propertyTypeId => $price) {
                if ($price !== null && $price !== '') {
                    PackagePricing::create([
                        'pricing_package_id' => $package->id,
                        'property_type_id' => $propertyTypeId,
                        'base_monthly_price' => $price,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.pricing-packages.index')
                ->with('success', 'Pricing package created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create package: ' . $e->getMessage()]);
        }
    }

    public function edit(PricingPackage $pricingPackage)
    {
        $pricingPackage->load('packagePricing');
        $propertyTypes = PropertyType::active()->orderBy('type_name')->get();
        
        // Create a map of property_type_id => price for easy access in the form
        $existingPrices = $pricingPackage->packagePricing->pluck('base_monthly_price', 'property_type_id');
        
        return view('admin.pricing-system.pricing-packages.edit', compact('pricingPackage', 'propertyTypes', 'existingPrices'));
    }

    public function update(Request $request, PricingPackage $pricingPackage)
    {
        $validated = $request->validate([
            'package_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'prices' => 'required|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Convert features from string to array
            $features = $validated['features'] 
                ? array_filter(array_map('trim', explode("\n", $validated['features']))) 
                : [];

            // Update the package
            $pricingPackage->update([
                'package_name' => $validated['package_name'],
                'description' => $validated['description'],
                'features' => $features,
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            // Delete existing pricing
            $pricingPackage->packagePricing()->delete();

            // Create new pricing for each property type with a price
            foreach ($validated['prices'] as $propertyTypeId => $price) {
                if ($price !== null && $price !== '') {
                    PackagePricing::create([
                        'pricing_package_id' => $pricingPackage->id,
                        'property_type_id' => $propertyTypeId,
                        'base_monthly_price' => $price,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.pricing-packages.index')
                ->with('success', 'Pricing package updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update package: ' . $e->getMessage()]);
        }
    }

    public function destroy(PricingPackage $pricingPackage)
    {
        DB::beginTransaction();
        try {
            // Delete pricing records first
            $pricingPackage->packagePricing()->delete();
            
            // Delete the package
            $pricingPackage->delete();
            
            DB::commit();

            return redirect()->route('admin.pricing-packages.index')
                ->with('success', 'Pricing package deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete package: ' . $e->getMessage()]);
        }
    }
}
