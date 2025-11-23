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
        $query = Property::with('user');

        // Role-based filtering
        if ($user->hasRole('Inspector')) {
            // Inspectors only see properties assigned to them
            $query->where('inspector_id', $user->id)
                  ->where('status', 'awaiting_inspection');
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
                $query->where('status', $request->status);
            } else {
                // Default to pending approval for admins
                $query->where('status', 'pending_approval');
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
     * Assign project manager and inspector to property.
     */
    public function assign(Request $request, Property $property)
    {
        $validated = $request->validate([
            'project_manager_id' => 'required|exists:users,id',
            'inspector_id' => 'required|exists:users,id',
            'inspection_scheduled_at' => 'nullable|date|after:now',
        ]);

        $property->project_manager_id = $validated['project_manager_id'];
        $property->inspector_id = $validated['inspector_id'];
        $property->assigned_at = now();
        
        if (!empty($validated['inspection_scheduled_at'])) {
            $property->inspection_scheduled_at = $validated['inspection_scheduled_at'];
        }
        
        $property->status = 'awaiting_inspection';
        $property->save();

        return redirect()->back()
            ->with('success', "Staff assigned successfully! Property is now awaiting inspection.");
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
