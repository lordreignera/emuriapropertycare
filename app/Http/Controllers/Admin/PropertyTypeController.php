<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    public function index()
    {
        $propertyTypes = PropertyType::orderBy('type_name')->get();
        return view('admin.pricing-system.property-types.index', compact('propertyTypes'));
    }

    public function create()
    {
        return view('admin.pricing-system.property-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:property_types,name',
            'slug' => 'required|string|max:255|unique:property_types,slug',
            'description' => 'nullable|string',
            'uses_residential_pricing' => 'boolean',
            'uses_commercial_pricing' => 'boolean',
            'is_active' => 'boolean',
        ]);

        PropertyType::create($validated);

        return redirect()->route('admin.property-types.index')
            ->with('success', 'Property type created successfully.');
    }

    public function edit(PropertyType $propertyType)
    {
        return view('admin.pricing-system.property-types.edit', compact('propertyType'));
    }

    public function update(Request $request, PropertyType $propertyType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:property_types,name,' . $propertyType->id,
            'slug' => 'required|string|max:255|unique:property_types,slug,' . $propertyType->id,
            'description' => 'nullable|string',
            'uses_residential_pricing' => 'boolean',
            'uses_commercial_pricing' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $propertyType->update($validated);

        return redirect()->route('admin.property-types.index')
            ->with('success', 'Property type updated successfully.');
    }

    public function destroy(PropertyType $propertyType)
    {
        $propertyType->delete();

        return redirect()->route('admin.property-types.index')
            ->with('success', 'Property type deleted successfully.');
    }
}
