<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InspectionController extends Controller
{
    /**
     * Display a listing of properties awaiting inspection.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Base query for properties awaiting inspection
        $query = Property::with(['user', 'inspector', 'projectManager'])
            ->where('status', 'awaiting_inspection');

        // If user is an inspector, only show properties assigned to them
        if ($user->hasRole('Inspector')) {
            $query->where('inspector_id', $user->id);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            if ($request->status === 'scheduled') {
                $query->whereNotNull('inspection_scheduled_at');
            } elseif ($request->status === 'unscheduled') {
                $query->whereNull('inspection_scheduled_at');
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $properties = $query->orderBy('inspection_scheduled_at', 'asc')
            ->orderBy('assigned_at', 'asc')
            ->paginate(15);

        return view('admin.inspections.index', compact('properties'));
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

        return view('admin.inspections.create', compact('property'));
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
