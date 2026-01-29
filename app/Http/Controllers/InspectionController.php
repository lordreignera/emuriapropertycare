<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InspectionController extends Controller
{
    /**
     * Display a listing of inspections.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Base query for inspections
        $query = Inspection::with(['property.user', 'property.projectManager', 'inspector', 'assignedBy', 'project.projectManager'])
            ->whereNotNull('property_id');

        // Filter by status if provided
        if ($request->filled('status')) {
            if ($request->status === 'scheduled') {
                // Show inspections that are scheduled and paid but not yet completed
                $query->where('inspection_fee_status', 'paid')
                      ->where('status', 'scheduled');
            } elseif ($request->status === 'in_progress') {
                $query->where('status', 'in_progress');
            } elseif ($request->status === 'completed') {
                $query->where('status', 'completed');
            }
        } else {
            // By default, show scheduled and in_progress inspections
            $query->whereIn('status', ['scheduled', 'in_progress']);
        }

        // If user is an inspector, only show inspections assigned to them
        if ($user->hasRole('Inspector')) {
            $query->where('inspector_id', $user->id);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('property', function($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $inspections = $query->orderBy('scheduled_date', 'asc')
            ->paginate(15);

        return view('admin.inspections.index', compact('inspections'));
    }

    /**
     * Show the form for creating a new inspection.
     */
    public function create(Request $request)
    {
        $propertyId = $request->get('property_id');
        
        if (!$propertyId) {
            return redirect()->route('inspections.index')
                ->with('error', 'Property ID is required to start an inspection.');
        }

        $property = Property::with(['user', 'inspector', 'projectManager'])
            ->findOrFail($propertyId);

        // Check if user has permission to inspect this property
        $user = Auth::user();
        if ($user->hasRole('Inspector') && $property->inspector_id !== $user->id) {
            abort(403, 'You are not assigned to inspect this property.');
        }

        // Get existing inspection if it exists
        $inspection = Inspection::where('property_id', $property->id)
            ->where('inspection_fee_status', 'paid')
            ->first();

        // Load ALL CPI lookup data from database
        $pricingPackages = \App\Models\PricingPackage::with(['packagePricing' => function($query) {
            $query->where('is_active', true);
        }])
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();
        
        // Load CPI domains with their factors
        $cpiDomains = \App\Models\CpiDomain::with(['activeFactors'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        $supplyMaterials = \App\Models\SupplyLineMaterial::where('is_active', true)->orderBy('sort_order')->get();
        $ageBrackets = \App\Models\AgeBracket::where('is_active', true)->orderBy('sort_order')->get();
        $containmentCategories = \App\Models\ContainmentCategory::where('is_active', true)->orderBy('sort_order')->get();
        $crawlAccessCategories = \App\Models\CrawlAccessCategory::where('is_active', true)->orderBy('sort_order')->get();
        $roofAccessCategories = \App\Models\RoofAccessCategory::where('is_active', true)->orderBy('sort_order')->get();
        $equipmentRequirements = \App\Models\EquipmentRequirement::where('is_active', true)->orderBy('sort_order')->get();
        $complexityCategories = \App\Models\ComplexityCategory::where('is_active', true)->orderBy('sort_order')->get();
        
        // Load CPI band ranges with their multipliers
        $cpiBandRanges = \App\Models\CpiBandRange::with('multiplier')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        // Load residential size tiers for size factor calculation
        $residentialSizeTiers = \App\Models\ResidentialSizeTier::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('admin.inspections.form-cpi', compact(
            'property',
            'inspection',
            'pricingPackages',
            'cpiDomains',
            'supplyMaterials',
            'ageBrackets',
            'containmentCategories',
            'crawlAccessCategories',
            'roofAccessCategories',
            'equipmentRequirements',
            'complexityCategories',
            'cpiBandRanges',
            'residentialSizeTiers'
        ));
    }

    /**
     * Store a newly created inspection in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'inspection_date' => 'required|date',
            'inspection_type' => 'required|in:initial,routine,follow-up,emergency',
            'status' => 'required|in:scheduled,in_progress,completed',
            'overall_condition' => 'nullable|in:excellent,good,fair,poor',
            'notes' => 'nullable|string',
            'issues_found' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'photos.*' => 'nullable|image|max:10240', // 10MB max per photo
            'report' => 'nullable|file|mimes:pdf|max:20480', // 20MB max
        ]);

        $property = Property::findOrFail($validated['property_id']);

        // Create or find project for this property
        $project = \App\Models\Project::firstOrCreate(
            ['property_id' => $property->id],
            [
                'name' => 'Property Inspection - ' . $property->property_name,
                'description' => 'Initial inspection for ' . $property->property_name,
                'status' => 'pending',
            ]
        );

        // Build findings array
        $findings = [];
        if (!empty($validated['issues_found'])) {
            $findings['issues'] = $validated['issues_found'];
        }
        if (!empty($validated['recommendations'])) {
            $findings['recommendations'] = $validated['recommendations'];
        }
        if (!empty($validated['overall_condition'])) {
            $findings['condition'] = $validated['overall_condition'];
        }

        // Create inspection record
        $inspection = new Inspection();
        $inspection->project_id = $project->id;
        $inspection->inspector_id = Auth::id();
        $inspection->assigned_by = $property->project_manager_id ?? Auth::id();
        $inspection->scheduled_date = $validated['inspection_date'];
        
        if ($validated['status'] === 'completed') {
            $inspection->completed_date = now();
        }
        
        $inspection->status = $validated['status'];
        $inspection->summary = $validated['notes'] ?? 'Inspection for ' . $property->property_name;
        $inspection->findings = $findings;

        // Handle photos upload
        if ($request->hasFile('photos')) {
            $photosPaths = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('inspections/photos', 'public');
                $photosPaths[] = $path;
            }
            $inspection->photos = $photosPaths;
        }

        // Handle report upload
        if ($request->hasFile('report')) {
            $reportPath = $request->file('report')->store('inspections/reports', 'public');
            $inspection->report_file = $reportPath;
        }

        $inspection->save();

        // Update property status if inspection is completed
        if ($validated['status'] === 'completed') {
            $property->status = 'inspected';
            $property->save();
        }

        $message = $request->action === 'save_draft' 
            ? 'Inspection saved as draft successfully!' 
            : 'Inspection submitted successfully!';

        return redirect()->route('inspections.index')
            ->with('success', $message);
    }

    /**
     * Display the specified inspection.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified inspection.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified inspection in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified inspection from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
