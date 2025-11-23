<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $properties = Property::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('client.properties.index', compact('properties'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('client.properties.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_name' => 'required|string|max:255',
            'property_brand' => 'nullable|string|max:50',
            'type' => 'required|in:house,townhome,condo,duplex,multi-unit',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            
            // Address
            'property_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|in:Canada,USA,El Salvador',
            
            // Size
            'square_footage_interior' => 'nullable|numeric|min:0',
            'square_footage_green' => 'nullable|numeric|min:0',
            'square_footage_paved' => 'nullable|numeric|min:0',
            'square_footage_extra' => 'nullable|numeric|min:0',
            
            // Owner
            'owner_first_name' => 'required|string|max:100',
            'owner_phone' => 'required|string|max:20',
            'owner_email' => 'required|email|max:255',
            
            // Admin (optional)
            'admin_first_name' => 'nullable|string|max:100',
            'admin_last_name' => 'nullable|string|max:100',
            'admin_email' => 'nullable|email|max:255',
            'admin_phone' => 'nullable|string|max:20',
            
            // Occupancy
            'occupied_by' => 'nullable|in:owner,family,tenants,mixed',
            'has_pets' => 'nullable|boolean',
            'has_kids' => 'nullable|boolean',
            'has_tenants' => 'nullable|boolean',
            'number_of_units' => 'nullable|integer|min:1',
            
            // Details
            'personality' => 'nullable|in:calm,busy,luxury,high-use',
            'known_problems' => 'nullable|string',
            'sensitivities' => 'nullable|string',
            
            // Files
            'property_photos.*' => 'nullable|image|max:10240', // 10MB max
            'blueprint_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480', // 20MB max
        ]);

        // Generate property code
        $validated['property_code'] = Property::generatePropertyCode($validated['property_brand'] ?? null);
        
        // Set user_id
        $validated['user_id'] = auth()->id();
        
        // Set status as pending approval
        $validated['status'] = 'pending_approval';
        
        // Calculate total square footage
        $validated['total_square_footage'] = 
            ($validated['square_footage_interior'] ?? 0) + 
            ($validated['square_footage_green'] ?? 0) + 
            ($validated['square_footage_paved'] ?? 0) + 
            ($validated['square_footage_extra'] ?? 0);
        
        // Generate tenant password if has_tenants is true
        if ($validated['has_tenants'] ?? false) {
            $validated['tenant_common_password'] = Property::generateTenantPassword();
        }
        
        // Convert sensitivities to array (split by comma)
        if (!empty($validated['sensitivities'])) {
            $validated['sensitivities'] = array_map('trim', explode(',', $validated['sensitivities']));
        }
        
        // Handle property photos upload
        if ($request->hasFile('property_photos')) {
            $photos = [];
            foreach ($request->file('property_photos') as $photo) {
                $path = $photo->store('properties/photos', 'public');
                $photos[] = $path;
            }
            $validated['property_photos'] = $photos;
        }
        
        // Handle blueprint file upload
        if ($request->hasFile('blueprint_file')) {
            $validated['blueprint_file'] = $request->file('blueprint_file')->store('properties/blueprints', 'public');
        }
        
        // Create property
        $property = Property::create($validated);
        
        return redirect()
            ->route('client.properties.show', $property->id)
            ->with('success', 'Property submitted successfully! It will be reviewed by our team.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property)
    {
        // Ensure user can only view their own properties
        if ($property->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this property.');
        }

        return view('client.properties.show', compact('property'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property)
    {
        // Ensure user can only edit their own properties
        if ($property->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this property.');
        }

        // Only allow editing if not yet approved
        if ($property->status === 'approved') {
            return redirect()
                ->route('client.properties.show', $property->id)
                ->with('info', 'This property is already approved. Please contact support to request changes.');
        }

        return view('client.properties.edit', compact('property'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Property $property)
    {
        // Ensure user can only update their own properties
        if ($property->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this property.');
        }

        // Only allow updating if not yet approved
        if ($property->status === 'approved') {
            return redirect()
                ->route('client.properties.show', $property->id)
                ->with('error', 'Cannot update approved properties. Please contact support.');
        }

        $validated = $request->validate([
            'property_name' => 'required|string|max:255',
            'type' => 'required|in:house,townhome,condo,duplex,multi-unit',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            
            // Address
            'property_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|in:Canada,USA,El Salvador',
            
            // Size
            'square_footage_interior' => 'nullable|numeric|min:0',
            'square_footage_green' => 'nullable|numeric|min:0',
            'square_footage_paved' => 'nullable|numeric|min:0',
            'square_footage_extra' => 'nullable|numeric|min:0',
            
            // Owner
            'owner_first_name' => 'required|string|max:100',
            'owner_phone' => 'required|string|max:20',
            'owner_email' => 'required|email|max:255',
            
            // Admin (optional)
            'admin_first_name' => 'nullable|string|max:100',
            'admin_last_name' => 'nullable|string|max:100',
            'admin_email' => 'nullable|email|max:255',
            'admin_phone' => 'nullable|string|max:20',
            
            // Occupancy
            'occupied_by' => 'nullable|in:owner,family,tenants,mixed',
            'has_pets' => 'nullable|boolean',
            'has_kids' => 'nullable|boolean',
            'has_tenants' => 'nullable|boolean',
            'number_of_units' => 'nullable|integer|min:1',
            
            // Details
            'personality' => 'nullable|in:calm,busy,luxury,high-use',
            'known_problems' => 'nullable|string',
            'sensitivities' => 'nullable|string',
            
            // Files
            'property_photos.*' => 'nullable|image|max:10240',
            'blueprint_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        // Calculate total square footage
        $validated['total_square_footage'] = 
            ($validated['square_footage_interior'] ?? 0) + 
            ($validated['square_footage_green'] ?? 0) + 
            ($validated['square_footage_paved'] ?? 0) + 
            ($validated['square_footage_extra'] ?? 0);
        
        // Convert sensitivities to array
        if (!empty($validated['sensitivities'])) {
            $validated['sensitivities'] = array_map('trim', explode(',', $validated['sensitivities']));
        }
        
        // Handle new property photos
        if ($request->hasFile('property_photos')) {
            // Delete old photos
            if ($property->property_photos) {
                foreach ($property->property_photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }
            
            $photos = [];
            foreach ($request->file('property_photos') as $photo) {
                $path = $photo->store('properties/photos', 'public');
                $photos[] = $path;
            }
            $validated['property_photos'] = $photos;
        }
        
        // Handle new blueprint
        if ($request->hasFile('blueprint_file')) {
            // Delete old blueprint
            if ($property->blueprint_file) {
                Storage::disk('public')->delete($property->blueprint_file);
            }
            
            $validated['blueprint_file'] = $request->file('blueprint_file')->store('properties/blueprints', 'public');
        }
        
        $property->update($validated);
        
        return redirect()
            ->route('client.properties.show', $property->id)
            ->with('success', 'Property updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        // Ensure user can only delete their own properties
        if ($property->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this property.');
        }

        // Only allow deleting if pending or rejected
        if ($property->status === 'approved') {
            return redirect()
                ->route('client.properties.index')
                ->with('error', 'Cannot delete approved properties. Please contact support.');
        }

        // Delete photos
        if ($property->property_photos) {
            foreach ($property->property_photos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }
        
        // Delete blueprint
        if ($property->blueprint_file) {
            Storage::disk('public')->delete($property->blueprint_file);
        }
        
        $property->delete();
        
        return redirect()
            ->route('client.properties.index')
            ->with('success', 'Property deleted successfully!');
    }
}
