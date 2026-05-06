<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use App\Notifications\PropertyRegisteredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $properties = Property::with(['latestInspection', 'latestCompletedInspection'])
            ->where('user_id', auth()->id())
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
            'type' => 'required|in:residential,commercial,mixed_use',
            'property_subtype' => 'nullable|in:house,townhome,condo,duplex,multi_unit',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            
            // Additional fields for property types
            'residential_units' => 'nullable|integer|min:1',
            'mixed_use_commercial_weight' => 'nullable|numeric|min:0|max:100',
            
            // Address
            'property_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            
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
            'personality_notes' => 'nullable|string|max:2000',
            'known_problems' => 'nullable',
            'known_problem_images.*' => 'nullable|image|max:10240',
            'sensitivities' => 'nullable|array',
            'sensitivities.*' => 'string|max:255',
            'home_journey' => 'nullable|array',
            'home_journey.*' => 'string|max:255',
            'home_feel' => 'nullable|array',
            'home_feel.*' => 'string|max:255',
            'care_goals' => 'nullable|array',
            'care_goals.*' => 'string|in:walls_paint_care,trim_woodwork_finishing,flooring_care_patching,appliance_support,electrical_safety,moisture_leak_prevention,hvac_filters_program,pest_prevention_sealing,gutter_cleaning_drainage,pressure_washing,garden_lawn_care,tree_pruning_yard_health,seasonal_prep,travel_away_watch,moving_in_out_service,pre_sale_refresh',
            
            // Files
            'property_photos.*' => 'nullable|image|max:10240', // 10MB max
            'blueprint_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,dwg,dxf|max:20480', // 20MB max
        ]);

        $blueprintError = $this->validateBlueprintQuality($request);
        if ($blueprintError) {
            return back()->withErrors(['blueprint_file' => $blueprintError])->withInput();
        }

        // Ensure DB-required unit count is never null
        // (empty input from hidden/conditional field can validate as null)
        $validated['number_of_units'] = (int) ($validated['number_of_units'] ?? 1);

        // Normalize booleans from optional checkbox/toggle inputs
        $validated['has_tenants'] = (bool) ($validated['has_tenants'] ?? false);
        $validated['has_pets'] = (bool) ($validated['has_pets'] ?? false);
        $validated['has_kids'] = (bool) ($validated['has_kids'] ?? false);

        // Square footage columns are NOT NULL in the DB with default(0).
        // Validation allows null (optional fields), but we must store 0 instead of null.
        $validated['square_footage_interior'] = (float) ($validated['square_footage_interior'] ?? 0);
        $validated['square_footage_green']    = (float) ($validated['square_footage_green']    ?? 0);
        $validated['square_footage_paved']    = (float) ($validated['square_footage_paved']    ?? 0);
        $validated['square_footage_extra']    = (float) ($validated['square_footage_extra']    ?? 0);

        // Generate property code
        $validated['property_code'] = Property::generatePropertyCode($validated['property_brand'] ?? null);
        
        // Set user_id
        $validated['user_id'] = auth()->id();
        
        // Set status as pending approval
        $validated['status'] = 'pending_approval';
        
        // Calculate total square footage
        $validated['total_square_footage'] =
            $validated['square_footage_interior'] +
            $validated['square_footage_green']    +
            $validated['square_footage_paved']    +
            $validated['square_footage_extra'];
        
        // Generate tenant password if has_tenants is true
        if ($validated['has_tenants'] ?? false) {
            $validated['tenant_common_password'] = Property::generateTenantPassword();
        }
        
        $knownProblemsList = $this->normalizeListInput($request->input('known_problems'));
        $validated['known_problems'] = !empty($knownProblemsList) ? implode(', ', $knownProblemsList) : null;

        $validated['sensitivities'] = $this->normalizeListInput($request->input('sensitivities'));
        $validated['home_journey'] = array_values(array_filter((array) ($validated['home_journey'] ?? [])));
        $validated['home_feel'] = array_values(array_filter((array) ($validated['home_feel'] ?? [])));
        $validated['care_goals'] = array_values(array_filter((array) ($validated['care_goals'] ?? [])));

        $disk = config('filesystems.default', 's3');
        
        // Handle property photos upload
        if ($request->hasFile('property_photos')) {
            $photos = [];
            foreach ($request->file('property_photos') as $photo) {
                $path = $photo->store('properties/photos', $disk);
                $photos[] = $path;
            }
            $validated['property_photos'] = $photos;
        }
        
        // Handle blueprint file upload
        if ($request->hasFile('blueprint_file')) {
            $validated['blueprint_file'] = $request->file('blueprint_file')->store('properties/blueprints', $disk);
        }

        if ($request->hasFile('known_problem_images')) {
            $validated['known_problem_images'] = $this->storeKnownProblemImages($request, $disk);
        }
        
        // Create property
        $property = Property::create($validated);

        $adminRecipients = User::role(['Super Admin', 'Administrator', 'Project Manager', 'Inspector', 'Technician', 'Store Manager'])
            ->get()
            ->unique('id')
            ->values();

        if ($adminRecipients->isNotEmpty()) {
            Notification::send($adminRecipients, new PropertyRegisteredNotification(
                propertyId: (int) $property->id,
                propertyCode: (string) ($property->property_code ?? 'N/A'),
                propertyName: (string) ($property->property_name ?? 'Property'),
                city: (string) ($property->city ?? 'Unknown city'),
                clientName: (string) (auth()->user()->name ?? 'Client'),
            ));
        }
        
        return redirect()
            ->route('client.properties.show', $property->id)
            ->with('success', 'Property added successfully! Next step: Schedule and pay for your property inspection/assessment to get started.');
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
            'property_brand' => 'nullable|string|max:50',
            'type' => 'required|in:residential,commercial,mixed_use',
            'property_subtype' => 'nullable|in:house,townhome,condo,duplex,multi_unit',
            'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
            'residential_units' => 'nullable|integer|min:1',
            'mixed_use_commercial_weight' => 'nullable|numeric|min:0|max:100',
            
            // Address
            'property_address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            
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
            'personality_notes' => 'nullable|string|max:2000',
            'known_problems' => 'nullable',
            'known_problem_images.*' => 'nullable|image|max:10240',
            'sensitivities' => 'nullable|array',
            'sensitivities.*' => 'string|max:255',
            'home_journey' => 'nullable|array',
            'home_journey.*' => 'string|max:255',
            'home_feel' => 'nullable|array',
            'home_feel.*' => 'string|max:255',
            'care_goals' => 'nullable|array',
            'care_goals.*' => 'string|in:walls_paint_care,trim_woodwork_finishing,flooring_care_patching,appliance_support,electrical_safety,moisture_leak_prevention,hvac_filters_program,pest_prevention_sealing,gutter_cleaning_drainage,pressure_washing,garden_lawn_care,tree_pruning_yard_health,seasonal_prep,travel_away_watch,moving_in_out_service,pre_sale_refresh',
            
            // Files
            'property_photos.*' => 'nullable|image|max:10240',
            'blueprint_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,dwg,dxf|max:20480',
        ]);

        $blueprintError = $this->validateBlueprintQuality($request);
        if ($blueprintError) {
            return back()->withErrors(['blueprint_file' => $blueprintError])->withInput();
        }

        // Keep required unit count safe for DB writes
        $validated['number_of_units'] = (int) ($validated['number_of_units'] ?? 1);

        // Normalize booleans from optional checkbox/toggle inputs
        $validated['has_tenants'] = (bool) ($validated['has_tenants'] ?? false);
        $validated['has_pets'] = (bool) ($validated['has_pets'] ?? false);
        $validated['has_kids'] = (bool) ($validated['has_kids'] ?? false);

        // Square footage columns are NOT NULL in the DB — ensure 0 instead of null.
        $validated['square_footage_interior'] = (float) ($validated['square_footage_interior'] ?? 0);
        $validated['square_footage_green']    = (float) ($validated['square_footage_green']    ?? 0);
        $validated['square_footage_paved']    = (float) ($validated['square_footage_paved']    ?? 0);
        $validated['square_footage_extra']    = (float) ($validated['square_footage_extra']    ?? 0);

        // Calculate total square footage
        $validated['total_square_footage'] =
            $validated['square_footage_interior'] +
            $validated['square_footage_green']    +
            $validated['square_footage_paved']    +
            $validated['square_footage_extra'];
        
        $knownProblemsList = $this->normalizeListInput($request->input('known_problems'));
        $validated['known_problems'] = !empty($knownProblemsList) ? implode(', ', $knownProblemsList) : null;

        $validated['sensitivities'] = $this->normalizeListInput($request->input('sensitivities'));
        $validated['home_journey'] = array_values(array_filter((array) ($validated['home_journey'] ?? [])));
        $validated['home_feel'] = array_values(array_filter((array) ($validated['home_feel'] ?? [])));
        $validated['care_goals'] = array_values(array_filter((array) ($validated['care_goals'] ?? [])));

        $disk = config('filesystems.default', 's3');
        
        // Handle new property photos
        if ($request->hasFile('property_photos')) {
            // Delete old photos
            if ($property->property_photos) {
                foreach ($property->property_photos as $photo) {
                    Storage::disk($disk)->delete($photo);
                }
            }
            
            $photos = [];
            foreach ($request->file('property_photos') as $photo) {
                $path = $photo->store('properties/photos', $disk);
                $photos[] = $path;
            }
            $validated['property_photos'] = $photos;
        }
        
        // Handle new blueprint
        if ($request->hasFile('blueprint_file')) {
            // Delete old blueprint
            if ($property->blueprint_file) {
                Storage::disk($disk)->delete($property->blueprint_file);
            }
            
            $validated['blueprint_file'] = $request->file('blueprint_file')->store('properties/blueprints', $disk);
        }

        if ($request->hasFile('known_problem_images')) {
            if ($property->known_problem_images) {
                foreach ($property->known_problem_images as $image) {
                    Storage::disk($disk)->delete($image);
                }
            }

            $validated['known_problem_images'] = $this->storeKnownProblemImages($request, $disk);
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

        $disk = config('filesystems.default', 's3');

        // Delete photos
        if ($property->property_photos) {
            foreach ($property->property_photos as $photo) {
                Storage::disk($disk)->delete($photo);
            }
        }
        
        // Delete blueprint
        if ($property->blueprint_file) {
            Storage::disk($disk)->delete($property->blueprint_file);
        }

        if ($property->known_problem_images) {
            foreach ($property->known_problem_images as $image) {
                Storage::disk($disk)->delete($image);
            }
        }
        
        $property->delete();
        
        return redirect()
            ->route('client.properties.index')
            ->with('success', 'Property deleted successfully!');
    }

    protected function normalizeListInput($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($item) => $item !== ''));
        }

        $raw = trim((string) $value);
        if ($raw === '' || strtolower($raw) === 'null') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $raw)), fn ($item) => $item !== ''));
    }

    protected function validateBlueprintQuality(Request $request): ?string
    {
        if (!$request->hasFile('blueprint_file')) {
            return null;
        }

        $file = $request->file('blueprint_file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'dwg', 'dxf'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return 'Unsupported blueprint format. Allowed: PDF, JPG/JPEG, PNG, DWG, DXF.';
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $imageInfo = @getimagesize($file->getRealPath());
            if (!$imageInfo || count($imageInfo) < 2) {
                return 'Invalid image file. Please upload a clear PNG/JPG image.';
            }

            $width = (int) $imageInfo[0];
            $height = (int) $imageInfo[1];
            $shortestSide = min($width, $height);

            if ($shortestSide < 1000) {
                return 'Image blueprint resolution is too low. Please upload at least 1000px on the shortest side.';
            }
        }

        return null;
    }

    protected function storeKnownProblemImages(Request $request, string $disk): array
    {
        $images = [];

        foreach ((array) $request->file('known_problem_images', []) as $image) {
            if ($image) {
                $images[] = $image->store('properties/known-problems', $disk);
            }
        }

        return $images;
    }
}
