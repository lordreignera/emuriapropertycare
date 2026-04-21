<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties (for admin approval).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Property::with([
            'user',
            'inspections' => function ($q) {
                $q->latest('created_at');
            },
        ]);

        // Role-based filtering
        if ($user->hasRole('Inspector')) {
            // Inspectors only see properties assigned to them (property-level or inspection-level)
            $query->where(function ($q) use ($user) {
                $q->where('inspector_id', $user->id)
                  ->orWhereHas('inspections', function ($inspectionQuery) use ($user) {
                      $inspectionQuery->where('inspector_id', $user->id)
                          ->whereIn('status', ['scheduled', 'in_progress', 'completed']);
                  });
            });

            // Inspector status filtering
            if ($request->filled('status')) {
                $status = $request->status;

                if ($status === 'awaiting_inspection') {
                    $query->whereHas('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('inspection_fee_status', 'paid')
                            ->where('status', 'scheduled');
                    })
                    ->whereDoesntHave('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('status', 'completed');
                    });
                } elseif ($status === 'not_inspected' || $status === 'active') {
                    $query->whereDoesntHave('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('status', 'completed');
                    });
                } elseif ($status === 'inspected_completed') {
                    $query->whereHas('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('status', 'completed');
                    });
                } else {
                    $query->where('status', $status);
                }
            }
        } elseif ($user->hasRole('Project Manager')) {
            // Project Managers only see properties assigned to them
            $query->where('project_manager_id', $user->id)
                  ->where('status', 'awaiting_inspection');
        } elseif ($user->hasRole('Technician')) {
            // Technicians only see properties with projects assigned to them
            $query->whereHas('projects', function($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        } else {
            // Admins see all properties with status filter
            // Filter by status
            if ($request->filled('status')) {
                $status = $request->status;
                
                if ($status === 'awaiting_inspection') {
                    // Properties with scheduled and paid inspections
                    $query->whereHas('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('inspection_fee_status', 'paid')
                            ->where('status', 'scheduled');
                    })
                    ->whereDoesntHave('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('status', 'completed');
                    });
                } elseif ($status === 'not_inspected' || $status === 'active') {
                    // Backward compatible: old "active" maps to "not inspected"
                    // Not inspected = no completed inspection yet
                    $query->whereDoesntHave('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('status', 'completed');
                    });
                } elseif ($status === 'inspected_completed') {
                    // Has at least one completed inspection
                    $query->whereHas('inspections', function ($inspectionQuery) {
                        $inspectionQuery->where('status', 'completed');
                    });
                } else {
                    // Other status filters
                    $query->where('status', $status);
                }
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('property_address', 'like', "%{$search}%");
            });
        }

        $properties = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.properties.index', compact('properties'));
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property)
    {
        $property->load('user', 'inspector', 'projectManager', 'projects');
        
        // Generate proper back URL based on user role
        $user = Auth::user();
        if ($user->hasRole('Inspector') || $user->hasRole('Project Manager') || $user->hasRole('Technician')) {
            $backUrl = route('properties.index');
        } else {
            $backUrl = url()->previous();
        }
        
        return view('admin.properties.show', compact('property', 'backUrl'));
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property)
    {
        return view('admin.properties.edit', compact('property'));
    }

    /**
     * Update the specified property (for approval/rejection).
     */
    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending_approval,approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $property->status = $validated['status'];

        if ($validated['status'] === 'approved') {
            $property->approved_at = now();
            $property->approved_by = Auth::id();
        } elseif ($validated['status'] === 'rejected') {
            $property->rejection_reason = $validated['rejection_reason'] ?? null;
        }

        $property->save();

        $statusMessage = ucfirst(str_replace('_', ' ', $validated['status']));
        
        return redirect()->route('properties.index')
            ->with('success', "Property '{$property->property_name}' has been {$statusMessage}!");
    }

    /**
     * Approve a property.
     */
    public function approve(Property $property)
    {
        $property->status = 'approved';
        $property->approved_at = now();
        $property->approved_by = Auth::id();
        $property->save();

        return redirect()->back()
            ->with('success', "Property '{$property->property_name}' has been approved!");
    }

    /**
     * Reject a property.
     */
    public function reject(Request $request, Property $property)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $property->status = 'rejected';
        $property->rejection_reason = $validated['rejection_reason'];
        $property->save();

        return redirect()->back()
            ->with('success', "Property '{$property->property_name}' has been rejected.");
    }

    /**
     * Assign project manager and inspector to a paid inspection.
     */
    public function assign(Request $request, Property $property)
    {
        $validated = $request->validate([
            'project_manager_id' => 'required|exists:users,id',
            'inspector_id'       => 'required|exists:users,id',
            'technician_id'      => 'nullable|exists:users,id',
        ]);

        // Find the most recent paid inspection for this property (any status)
        $inspection = $property->inspections()
            ->where('inspection_fee_status', 'paid')
            ->whereIn('status', ['scheduled', 'in_progress', 'completed'])
            ->latest('id')
            ->first();

        if (!$inspection) {
            // Fall back to any paid inspection
            $inspection = $property->inspections()
                ->where('inspection_fee_status', 'paid')
                ->latest('id')
                ->first();
        }

        if (!$inspection) {
            return redirect()->back()
                ->with('error', 'No paid inspection found for this property.');
        }

        // Verify roles
        $projectManager = User::findOrFail($validated['project_manager_id']);
        if (!$projectManager->hasRole('Project Manager')) {
            return redirect()->back()
                ->with('error', 'Selected user is not a project manager.');
        }

        $inspector = User::findOrFail($validated['inspector_id']);
        if (!$inspector->hasRole('Inspector')) {
            return redirect()->back()
                ->with('error', 'Selected user is not an inspector.');
        }

        $technician = null;
        if (!empty($validated['technician_id'])) {
            $technician = User::findOrFail($validated['technician_id']);
            if (!$technician->hasRole('Technician')) {
                return redirect()->back()
                    ->with('error', 'Selected user is not a technician.');
            }
        }

        // Assign inspection team
        $inspection->inspector_id  = $validated['inspector_id'];
        $inspection->technician_id = $validated['technician_id'] ?? null;
        $inspection->assigned_by   = Auth::id();
        $inspection->save();

        // Also update the property
        $property->project_manager_id  = $validated['project_manager_id'];
        $property->inspector_id        = $validated['inspector_id'];
        $property->assigned_at         = $property->assigned_at ?: now();
        $property->inspection_scheduled_at = $property->inspection_scheduled_at ?: $inspection->scheduled_date;
        $property->save();

        // Also update the project's manager if a project exists
        if ($inspection->project) {
            $inspection->project->update(['managed_by' => $validated['project_manager_id']]);
        }

        $successMsg = "Project Manager ({$projectManager->name}) and Inspector ({$inspector->name}) assigned successfully!";
        if ($technician) {
            $successMsg .= " Technician ({$technician->name}) also assigned.";
        }

        return redirect()->back()->with('success', $successMsg);
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property)
    {
        $propertyName = $property->property_name;
        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', "Property '{$propertyName}' has been deleted.");
    }
}
